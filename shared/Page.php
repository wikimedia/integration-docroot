<?php
require_once __DIR__ . '/Functions.php';
class Page {
	/** If the directory only has one subdirectory, redirect to it. */
	const INDEX_ALLOW_SKIP = 1;

	/** Append the directory name to the page name. */
	const INDEX_PREFIX = 2;

	/** Append the parent directory to the page name (if INDEX_PREFIX is on). */
	const INDEX_PARENT_PREFIX = 4;

	protected $site = 'Wikimedia';
	protected $pageName = false;
	protected $embeddedCSS = array();
	protected $scripts = array();
	protected $stylesheets = array();
	protected $content = '';
	protected $hasFooter = false;

	/**
	 * Absolute directory on file system to where the page is instantiated.
	 * Will be used to guess url path to shared libs.
	 *
	 * @var string
	 */
	protected $rootDir = false;
	protected $dir = false;
	protected $originalPath;
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
	 * @return string: URL path to integration portal root (with trailing slash).
	 */
	public function getRootPath() {
		$subpath = '';
		if ( $this->rootDir && $this->dir ) {
			// __DIR__ = /docroot/shared/
			$docrootRepoDir = dirname( __DIR__ );
			if ( strpos( $this->rootDir, $docrootRepoDir ) === 0 && strpos( $this->dir, $this->rootDir ) === 0 ) {
				$path = $this->dir;
				while ( $path !== $this->rootDir && strpos( $path, $this->rootDir ) === 0 ) {
					$path = dirname( $path );
					if ( $subpath !== '' ) {
						$subpath .= '/';
					}
					$subpath .= '..';
				}
			}
		}
		return $subpath === '' ? '.' : $subpath;
	}

	/**
	 * @param string $path
	 */
	public function setLibPath( $path ) {
		$this->libPath = $path;
	}

	/**
	 * @return string: URL path to shared/lib (without trailing slash).
	 */
	public function getLibPath() {
		if ( $this->libPath ) {
			return $this->libPath;
		}
		return $this->getRootPath() . '/lib';
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

		if( !file_exists( $file ) ) {
			return false;
		}

		$content = file_get_contents( $file );
		if( $content === false ) {
			# TODO output an error page?
			return false;
		}
		$this->content .= trim( $content );
	}

	protected function processHtmlContent( $content, $indent = '' ) {
		$content = str_replace( '{{ROOT}}', htmlspecialchars( $this->getRootPath() ), $content );
		return $indent . implode( "\n$indent", explode( "\n", $content ) );
	}

	protected function getNavHtml() {
		return <<<HTML
<ul class="navbar-nav nav">
	<li><a href="https://gerrit.wikimedia.org/r/">Gerrit</a></li>
	<li><a href="https://integration.wikimedia.org/">Integration</a></li>
	<li><a href="https://doc.wikimedia.org/">Documentation</a></li>
</ul>
HTML;
	}

	public function flush() {
		$this->embedCSS('
/**
 * Logo
 */
.logo {
	vertical-align: middle;
	margin-right: 1em;
}
');

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
	<link rel="shortcut icon" href="//bits.wikimedia.org/favicon/wmf.ico">
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
			<a class="navbar-brand" href="<?php echo $rootPathHtml; ?>" title="Navigate to home of <?php echo htmlentities( $this->site ); ?>"><img src="//upload.wikimedia.org/wikipedia/commons/thumb/8/81/Wikimedia-logo.svg/48px-Wikimedia-logo.svg.png" width="24px">&nbsp;<?php echo htmlentities( $this->site ); ?></a>
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
<?php if ( $this->hasFooter ) { echo '<div class="push"></div>'; } ?>
</div><!-- /.page-wrap -->
<?php if ( $this->hasFooter ) { ?>
<div class="footer">
	<div class="container">
		<p class="muted credit">
			Questions? Comments? Concerns?<br>
			Contact <em>^demon</em>, <em>Krinkle</em> or <em>hashar</em> on
			either <a href="irc://irc.freenode.net/#wikimedia-dev">#wikimedia-dev</a>
			or <a href="irc://irc.freenode.net/#wikimedia-releng">#wikimedia-releng</a>.
		</p>
	</div>
</div><!-- /.footer -->
<?php } ?>
<script src="//bits.wikimedia.org/www.mediawiki.org/load.php?debug=false&amp;modules=jquery&amp;only=scripts&amp;raw=1"></script>
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
		$path = false;
		if ( isset( $_SERVER['REDIRECT_URL'] ) ) {
			// Rewritten by Apache to e.g. dir.php
			$path = $_SERVER['REDIRECT_URL'];
		} elseif ( isset( $_SERVER['SCRIPT_NAME'] ) ) {
			// Direct inclusion from e.g. cover/index.php
			$path = dirname( $_SERVER['SCRIPT_NAME'] );
		}
		if ( !$path || !isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
			Page::error( 'Invalid context.' );
		}
		// Use realpath() to prevent escalation through e.g. "../"
		// Note: realpath() also normalses paths to have no trailing slash
		$realPath = realpath( $_SERVER['DOCUMENT_ROOT'] . $path );
		if ( !$realPath || strpos( $realPath, $_SERVER['DOCUMENT_ROOT'] ) !== 0 ) {
			// Path escalation. Should be impossible as Apache normalises this.
			Page::error( 'Invalid context.' );
		}

		$p = self::newFromPageName( $pageName, $flags );
		$p->originalPath = $path;
		$p->setDir( $realPath );
		return $p;
	}

	public function handleDirIndex() {
		if ( $this->flags & self::INDEX_PREFIX ) {
			if ( $this->flags & self::INDEX_PARENT_PREFIX && strpos( $this->getRootPath(), '/' ) !== false ) {
				$this->pageName .= basename( dirname( $this->dir ) ) . ': ' . basename( $this->dir );
			} else {
				$this->pageName .= basename( $this->dir );
			}
		}
		$subDirPaths = glob( "{$this->dir}/*", GLOB_ONLYDIR );
		if ( $this->flags & self::INDEX_ALLOW_SKIP ) {
			if ( count( $subDirPaths ) === 1 ) {
				// Check whether the request URI ends in a slash and redirect to /a/b/c/target
				// as either ./target/ or ./c/target/. Redirects from requests no trailing slash
				// would otherwise end up at /a/b/target/.
				if ( substr( $this->originalPath, -1 ) === '/' ) {
					$target = './' . basename( $subDirPaths[0] ) . '/';
				} else {
					$target = './' . basename( $this->originalPath ) . '/' . basename( $subDirPaths[0] ) . '/';
				}
				header( "Location: $target" );
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

		$this->flush();
	}


	public static function error( $msg, $statusCode = 500 ) {
		$statusCode = (int)$statusCode;
		http_response_code( $statusCode );
		echo "<!DOCTYPE html><title>Error $statusCode</title><p>"
			. htmlspecialchars( $msg )
			. '</p>';
		exit;
	}

	private function __construct() {}
}
