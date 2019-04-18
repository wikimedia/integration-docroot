<?php

class Page {
	/** If the directory only has one subdirectory, redirect to it. */
	const INDEX_ALLOW_SKIP = 1;

	/** Append the directory name to the page name. */
	const INDEX_PREFIX = 2;

	/** Append the parent directory to the page name (if INDEX_PREFIX is on). */
	const INDEX_PARENT_PREFIX = 4;

	protected $site = 'Wikimedia';
	protected $pageName = false;
	protected $embeddedCSS = [];
	protected $scripts = [];
	protected $stylesheets = [];
	protected $content = '';
	protected $hasFooter = true;

	/**
	 * Absolute directory on file system to where the page is instantiated.
	 * Will be used to guess url path to shared libs.
	 *
	 * @var string
	 */
	protected $rootDir = false;
	protected $dir = false;
	protected $flags = 0;
	protected $libPath = false;

	/**
	 * @param string $pageName
	 * @param int $flags
	 */
	public static function newFromPageName( $pageName, $flags = 0 ) {
		$p = new static();
		$p->pageName = $pageName;
		$p->flags = $flags;
		return $p;
	}

	public static function newIndex() {
		$p = new static();
		$p->pageName = false;
		return $p;
	}

	public function setDir( $dir ) {
		$this->dir = $dir;
	}

	public function setRootDir( $dir ) {
		$this->rootDir = $dir;
	}

	/**
	 * @author Timo Tijhof, 2015
	 * @param string $fromPath
	 * @param string $toPath
	 * @return string Relative path
	 */
	public static function getPathTo( $fromPath, $toPath ) {
		if ( $fromPath === '' || $toPath === '' || $fromPath[0] !== '/' || $toPath[0] !== '/' ) {
			return '';
		}

		// Filter out double slashes, and empty matches from leading/trailing slash
		$from = array_values( array_filter(
			explode( '/', $fromPath ),
			function ( $part ) {
				return $part !== '';
			}
		) );
		$to = array_values( array_filter(
			explode( '/', $toPath ),
			function ( $part ) {
				return $part !== '';
			}
		) );

		// Remove source directory if it has no slash
		if ( substr( $fromPath, -1 ) !== '/' ) {
			array_pop( $from );
		}

		$relativePath = '';
		$i = 0;

		// Ignore common parts
		while ( isset( $from[$i] ) && isset( $to[$i] ) ) {
			if ( $from[$i] !== $to[$i] ) {
				break;
			}
			$i++;
		}

		// Move up from fromPath
		$j = count( $from );
		$relativePath .= str_repeat( '../', $j - $i );

		// Move down to toPath
		$down = array_slice( $to, $i );
		if ( $down ) {
			$relativePath .= implode( '/', $down );

			// Match target with slash
			if ( substr( $toPath, -1 ) === '/' ) {
				$relativePath .= '/';
			}
		}

		// Return at least "./" instead of "" so that it consistently ends in a slash.
		// This allows callers to safely append "/sub" directories to this, even on page served
		// from the root.
		return $relativePath !== '' ? $relativePath : './';
	}

	protected static function getRequestDir() {
		if ( isset( $_SERVER['REDIRECT_URL'] ) ) {
			// Rewritten by Apache to e.g. dir.php
			$path = $_SERVER['REDIRECT_URL'];
		} elseif ( isset( $_SERVER['SCRIPT_NAME'] ) ) {
			// Direct inclusion from e.g. cover/index.php
			$path = dirname( $_SERVER['SCRIPT_NAME'] ) . '/';
		}
		if ( !$path || !isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
			self::error( 'Invalid context.' );
		}
		// Use realpath() to prevent escalation through e.g. "../"
		// Note: realpath() also normalises paths to have no trailing slash
		$realPath = realpath( $_SERVER['DOCUMENT_ROOT'] . $path );
		if ( !$realPath || strpos( $realPath, $_SERVER['DOCUMENT_ROOT'] ) !== 0 ) {
			// Path escalation. Should be impossible as Apache normalises this.
			self::error( 'Invalid context.' );
		}
		if ( substr( $path, -1 ) === '/' ) {
			$realPath .= '/';
		}
		return $realPath;
	}

	/**
	 * @return string Relative URL path to site root (with trailing slash).
	 */
	public function getRootPath() {
		if ( !$this->rootDir ) {
			return './';
		}
		return self::getPathTo( self::getRequestDir(), $this->rootDir . '/' );
	}

	/**
	 * @return string Relative URL from site root to current url
	 */
	protected function getRequestPath() {
		if ( !$this->rootDir ) {
			return '';
		}
		return self::getPathTo( $this->rootDir . '/', self::getRequestDir() );
	}

	/**
	 * @param string $path
	 */
	public function setLibPath( $path ) {
		$this->libPath = $path;
	}

	/**
	 * @return string URL path to shared/lib (without trailing slash).
	 */
	public function getLibPath() {
		if ( $this->libPath ) {
			return $this->libPath;
		}
		return $this->getRootPath() . 'lib';
	}

	/**
	 * @param string $cssText
	 */
	public function embedCSS( $cssText ) {
		$this->embeddedCSS[] = trim( $cssText );
	}

	/**
	 * @param string filename relative to /shared
	 */
	public function embedCSSFile( $cssFile ) {
		$this->embeddedCSS[] = file_get_contents( __DIR__ . '/' . $cssFile );
	}

	/**
	 * @param string $src Path to script (may be relative)
	 */
	public function addScript( $src ) {
		$this->scripts[] = $src;
	}

	/**
	 * @param string $src Path to script (may be relative)
	 */
	public function addStylesheet( $src ) {
		$this->stylesheets[] = $src;
	}

	public function enableFooter() {
		$this->hasFooter = true;
	}

	/**
	 * @param string $html
	 */
	public function addHtmlContent( $html ) {
		$this->content .= trim( $html );
	}

	/**
	 * @param string $file
	 */
	public function addHtmlFile( $file ) {
		$isRelativePath = ( substr( $file, 0 ) !== '/' );

		if ( $isRelativePath && $this->dir ) {
			# We explicitly set a base path, prepend it
			# to relative paths.
			$file = $this->dir . DIRECTORY_SEPARATOR . $file;
		}

		if ( !file_exists( $file ) ) {
			return false;
		}

		$content = file_get_contents( $file );
		if ( $content === false ) {
			# TODO output an error page?
			return false;
		}
		$this->content .= trim( $content );
	}

	protected function processHtmlContent( $content, $indent = '' ) {
		return $indent . implode( "\n$indent", explode( "\n", $content ) );
	}

	protected function getNavItems() {
		return [
			'https://gerrit.wikimedia.org/r/' => 'Gerrit',
			'https://integration.wikimedia.org/' => 'Integration',
			'https://doc.wikimedia.org/' => 'Documentation',
		];
	}

	protected function fixNavUrl( $href ) {
		if ( $href[0] === '/' ) {
			// Expand relatively so that it works even if the site is mounted in a sub directory.
			$href = substr( $this->getRootPath(), 0, -1 ) . $href;
		}
		return $href;
	}

	protected function getNavHtml() {
		$html = '<ul class="navbar-nav nav">';
		$cur = $this->getRequestPath();
		foreach ( $this->getNavItems() as $href => $text ) {
			$active = ( $href === "/$cur" ? ' class="active"' : '' );
			$href = $this->fixNavUrl( $href );
			$html .= '<li' . $active . '><a href="' . htmlspecialchars( $href ) . '">' . htmlspecialchars( $text ) . '</a></li>';
		}
		$html .= '</ul>';
		return $html;
	}

	public function flush() {
	if ( $this->pageName ) {
		$this->embedCSSFile( 'header.css' );
	}
	if ( $this->hasFooter ) {
		$this->embedCSSFile( 'footer.css' );
	}

	$rootPathHtml = htmlspecialchars( $this->getRootPath() );
	$libPathHtml = htmlspecialchars( $this->getLibPath() );

?><!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
	<meta charset="utf-8">
	<title><?php
		if ( $this->pageName ) {
			echo htmlentities( $this->pageName . ' - ' . $this->site );
		} else {
			echo htmlentities( $this->site );
		};
	?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" href="https://www.wikimedia.org/static/favicon/wmf.ico">
	<link rel="stylesheet" href="<?php echo $libPathHtml; ?>/bootstrap/css/bootstrap.min.css">
<?php
	if ( count( $this->embeddedCSS ) ) {
		echo "\t<style>\n\t" . implode( "\n\t", explode( "\n", implode( "\n\n\n", $this->embeddedCSS ) ) ) . "\n\t</style>\n";
	}
?>
<?php
	foreach ( $this->stylesheets as $stylesheet ) {
		echo '<link rel="stylesheet" href="' . htmlspecialchars( $stylesheet ) . '">' . "\n";
	}
?>
</head>
<body>
<header class="navbar navbar-default navbar-static-top base-nav" id="top" role="banner">
	<div class="container">
		<div class="navbar-header">
			<a class="navbar-brand" href="<?php echo $rootPathHtml; ?>" title="Navigate to home of <?php echo htmlentities( $this->site ); ?>"><?php echo htmlentities( $this->site ); ?></a>
		</div>
		<nav class="navbar-collapse collapse">
<?php
	echo $this->processHtmlContent( $this->getNavHtml() );
?>
		</nav>
	</div>
</header>
<div class="page-wrap">
	<div class="container">
<?php
	if ( $this->pageName ) {
		echo '<h1 class="page-header">' . htmlentities( $this->pageName ) . '</h1>';
	}
?>
<?php
	echo $this->processHtmlContent( $this->content, "\t\t" );
?>
	</div><!-- /.container -->
<?php
	if ( $this->hasFooter ) {
		echo '<div class="push"></div>';
	}
?>
</div><!-- /.page-wrap -->
<?php if ( $this->hasFooter ) { ?>
<div class="footer">
	<div class="container"><div class="row">
		<p class="col-sm-8">
			More information on <a href="https://www.mediawiki.org/wiki/Continuous_integration">Continuous Integration</a> at www.mediawiki.org.
		</p>
		<p class="col-sm-4 text-right"><a href="https://www.wikimedia.org"><img src="https://www.wikimedia.org/static/images/wikimedia-button.png" srcset="https://www.wikimedia.org/static/images/wikimedia-button-2x.png 2x" width="88" height="31" alt="Wikimedia Foundation"></a></p>
	</div></div>
</div><!-- /.footer -->
<?php } ?>
<script src="<?php echo $libPathHtml; ?>/jquery.min.js"></script>
<script src="<?php echo $libPathHtml; ?>/bootstrap/js/bootstrap.min.js"></script>
<?php
	foreach ( $this->scripts as $script ) {
		echo '<script src="' . htmlspecialchars( $script ) . '"></script>' . "\n";
	}
?>
</body>
</html>
<?php
	}

	public static function newDirIndex( $pageName, $flags = 0 ) {
		$path = self::getRequestDir();

		if ( substr( $path, -1 ) !== '/' ) {
			// Enforce trailing slash for directory index
			http_response_code( 301 );
			header( 'Location: ' . basename( $path ) . '/' );
			exit;
		}

		$p = self::newFromPageName( $pageName, $flags );
		$p->setDir( $path );
		return $p;
	}

	protected function getDirIndexDirectories() {
		return glob( "{$this->dir}/*", GLOB_ONLYDIR );
	}

	public function handleDirIndex() {
		if ( $this->flags & self::INDEX_PREFIX ) {
			if ( $this->flags & self::INDEX_PARENT_PREFIX && strpos( $this->getRootPath(), '/' ) !== false ) {
				$this->pageName .= basename( dirname( $this->dir ) ) . ': ' . basename( $this->dir );
			} else {
				$this->pageName .= basename( $this->dir );
			}
		}

		$subDirPaths = $this->getDirIndexDirectories();
		if ( $this->flags & self::INDEX_ALLOW_SKIP ) {
			if ( count( $subDirPaths ) === 1 ) {
				header( 'Location: ./' . basename( $subDirPaths[0] ) . '/' );
				exit;
			}
		}

		if ( count( $subDirPaths ) === 0 ) {
			$this->addHtmlContent( '<div class="alert alert-warning" role="alert"><strong>Empty directory!</strong></div>' );
		} else {
			$this->addHtmlContent( '<ul class="nav nav-pills nav-stacked">' );
			foreach ( $subDirPaths as $path ) {
				$dirName = basename( $path );
				$this->addHtmlContent( '<li><a href="./' . htmlspecialchars( $dirName ) . '/">'
					. htmlspecialchars( $dirName )
					. '</a>'
				);
			}
			$this->addHtmlContent( '</ul>' );
		}
	}

	public static function error( $msg, $statusCode = 500 ) {
		$statusCode = (int)$statusCode;
		http_response_code( $statusCode );
		echo "<!DOCTYPE html><title>Error $statusCode</title><p>"
			. htmlspecialchars( $msg )
			. '</p>';
		exit;
	}

	private function __construct() {
	}
}
