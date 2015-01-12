<?php
class Page {
	protected $site = 'Wikimedia';
	protected $pageName = false;
	protected $embeddedCSS = array();
	protected $scripts = array();
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

	protected $libPath = false;

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
<ul class="nav">
	<li class="divider-vertical"></li>
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

.page-header {
	overflow: hidden;
}

.page-wrap {
	padding-top: 55px;
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
	<link rel="stylesheet" href="<?php echo $libPathHtml; ?>/bootstrap/css/bootstrap-responsive.min.css">
<?php
	if ( count( $this->embeddedCSS ) ) {
		echo "\t<style>\n\t" . implode( "\n\t", explode( "\n", implode( "\n\n\n", $this->embeddedCSS ) ) ) . "\n\t</style>\n";
	}
?>
</head>
<body>
<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
		<a class="brand" href="<?php echo $rootPathHtml; ?>" title="Navigate to home of <?php echo htmlentities( $this->site ); ?>"><img src="//upload.wikimedia.org/wikipedia/commons/thumb/8/81/Wikimedia-logo.svg/48px-Wikimedia-logo.svg.png" width="24px">&nbsp;<?php echo htmlentities( $this->site ); ?></a>
<?php
	echo $this->processHtmlContent( $this->getNavHtml() );
?>
		</div>
	</div>
</div>
<div class="page-wrap">
	<div class="container">
<?php
	if ( $this->pageName ) {
		echo '<div class="page-header"><h2>' . htmlentities( $this->pageName ) . '</h2></div>';
	}
?>
<?php
	echo $this->processHtmlContent( $this->content, "\t\t" );
?>
	</div><!-- /.container -->
<?php if ( $this->hasFooter ) { echo "\t<div class=\"push\"></div>\n"; } ?>
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

	private function __construct() {}
}
