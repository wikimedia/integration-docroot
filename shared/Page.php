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
	protected $flags = 0;

	/**
	 * @param string $pageName
	 */
	public static function newFromPageName( $pageName ) {
		$p = new static();
		$p->pageName = $pageName;
		return $p;
	}

	public static function newIndex() {
		$p = new static();
		$p->pageName = false;
		return $p;
	}

	/**
	 * @param string $pageName
	 * @param int $flags
	 */
	public static function newDirIndex( $pageName, $flags = 0 ) {
		$p = new static();
		$p->pageName = $pageName;
		$p->flags = $flags;
		return $p;
	}

	/** @return string[] */
	protected static function getRequestPath() {
		if ( isset( $_SERVER['REDIRECT_URL'] ) ) {
			// Rewritten by Apache to e.g. dir.php
			$path = $_SERVER['REDIRECT_URL'];
		} elseif ( isset( $_SERVER['SCRIPT_NAME'] ) ) {
			// Direct inclusion from e.g. cover/index.php
			$path = dirname( $_SERVER['SCRIPT_NAME'] ) . '/';
		}
		if ( !$path || !isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
			self::error( 'Invalid context.' );
			return;
		}
		// Use realpath() to prevent escalation through e.g. "../"
		// Note: realpath() also normalises paths to have no trailing slash
		$realPath = realpath( $_SERVER['DOCUMENT_ROOT'] . $path );
		if ( !$realPath || strpos( $realPath, $_SERVER['DOCUMENT_ROOT'] ) !== 0 ) {
			// Path escalation. Should be impossible as Apache normalises this.
			self::error( 'Invalid context.' );
			return;
		}
		if ( substr( $path, -1 ) === '/' ) {
			$realPath .= '/';
		}
		$urlPath = substr( $realPath, strlen( rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ) ) );

		return [
			'urlPath' => $urlPath,
			'fileDir' => $realPath,
		];
	}

	/**
	 * @return string URL Path from document root to current url
	 */
	public function getUrlPath() {
		return self::getRequestPath()['urlPath'];
	}

	public function getDir() {
		return self::getRequestPath()['fileDir'];
	}

	/**
	 * @return string URL to shared/lib directory (without trailing slash).
	 */
	public function getLibPath() {
		return '/lib';
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
	private function embedCSSFile( $cssFile ) {
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

	/**
	 * @param string $html
	 */
	public function addHtmlContent( $html ) {
		$this->content .= trim( $html );
	}

	/**
	 * @param string $file Absolute path
	 */
	public function addHtmlFile( $file ) {
		if ( substr( $file, 0, 1 ) !== '/' ) {
			throw new InvalidArgumentException( 'Illegal path' );
		}

		$content = @file_get_contents( $file );
		if ( $content === false ) {
			throw new RuntimeException( 'Unreadable file ' . basename( $file ) );
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

	protected function getNavHtml() {
		$html = '<ul class="navbar-nav nav">';
		$cur = $this->getUrlPath();
		foreach ( $this->getNavItems() as $href => $text ) {
			$active = ( $href === $cur ? ' class="active"' : '' );
			$html .= '<li' . $active . '><a href="' . htmlspecialchars( $href ) . '">' . htmlspecialchars( $text ) . '</a></li>';
		}
		$html .= '</ul>';
		return $html;
	}

	public function flush() {
		if ( $this->pageName ) {
			$this->embedCSSFile( 'header.css' );
		}
		$this->embedCSSFile( 'footer.css' );

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
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="stylesheet" href="<?php echo $libPathHtml; ?>/bootstrap/css/bootstrap.min.css">
<?php
		if ( count( $this->embeddedCSS ) ) {
			echo "\t<style>\n\t" . implode( "\n\t", explode( "\n", implode( "\n\n\n", $this->embeddedCSS ) ) ) . "\n\t</style>\n";
		}
		foreach ( $this->stylesheets as $stylesheet ) {
			echo '<link rel="stylesheet" href="' . htmlspecialchars( $stylesheet ) . '">' . "\n";
		}
?>
</head>
<body>
<header class="navbar navbar-default navbar-static-top base-nav" id="top" role="banner">
	<div class="container">
		<div class="navbar-header">
			<a class="navbar-brand" href="/" title="Navigate to home of <?php echo htmlentities( $this->site ); ?>"><?php echo htmlentities( $this->site ); ?></a>
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
		echo $this->processHtmlContent( $this->content, "\t\t" );
?>
	</div><!-- /.container -->
	<div class="push"></div>'
</div><!-- /.page-wrap -->
<div class="footer">
	<div class="container"><div class="row">
		<p class="col-sm-8">
			More information on <a href="https://www.mediawiki.org/wiki/Continuous_integration">Continuous Integration</a> at www.mediawiki.org.
		</p>
		<p class="col-sm-4 text-right"><a href="https://www.wikimedia.org"><img src="<?php echo $libPathHtml; ?>/wikimedia-button.png" srcset="<?php echo $libPathHtml; ?>/wikimedia-button-2x.png 2x" width="88" height="31" alt="Wikimedia Foundation"></a></p>
	</div></div>
</div><!-- /.footer -->
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

	protected function getDirIndexDirectories() {
		return glob( $this->getDir() . "/*", GLOB_ONLYDIR );
	}

	public function handleDirIndex() {
		if ( $this->flags & self::INDEX_PREFIX ) {
			if ( $this->flags & self::INDEX_PARENT_PREFIX && $this->getUrlPath() !== '/' ) {
				$this->pageName .= basename( dirname( $this->getDir() ) ) . ': ' . basename( $this->getDir() );
			} else {
				$this->pageName .= basename( $this->getDir() );
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
