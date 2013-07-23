<?php
class IntegrationPage {
	protected $site = 'Wikimedia';
	protected $pageName = 'Home';
	protected $embeddedCSS = array();
	protected $scripts = array();
	protected $content = '';
	protected $hasFooter = false;

	/**
	 * Absolute directory on file system
	 * to where the page is instantiated. Will be used to guess url path
	 * to bootstrap.
	 *
	 * @var string
	 */
	protected $rootDir = false;
	protected $dir = false;

	protected $bootstrapPath = false;

	/**
	 * @param string $pageName
	 */
	public static function newFromPageName( $pageName ) {
		$p = new static();
		$p->pageName = $pageName;
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
	public function setBootstrapPath( $path ) {
		$this->bootstrapPath = $path;
	}

	/**
	 * @return string: URL path to boostrap (without trailing slash).
	 */
	public function getBootstrapPath() {
		if ( $this->bootstrapPath ) {
			return $this->bootstrapPath;
		}
		return $this->getRootPath() . '/bootstrap';
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
');

	if ( $this->hasFooter ) {
		$this->embedCSSFile('footer.css');
	}

	$rootPathHtml = htmlspecialchars( $this->getRootPath() );
	$bootstrapPathHtml = htmlspecialchars( $this->getBootstrapPath() );

?><!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
	<meta charset="utf-8">
	<title><?php echo htmlentities( $this->pageName . ' - ' . $this->site ); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" href="//bits.wikimedia.org/favicon/wmf.ico">
	<style>body { padding-top: 40px } </style>
	<link rel="stylesheet" href="<?php echo $bootstrapPathHtml; ?>/css/bootstrap.min.css">
	<link rel="stylesheet" href="<?php echo $bootstrapPathHtml; ?>/css/bootstrap-responsive.min.css">
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
		<a class="brand" href="<?php echo $rootPathHtml; ?>"><img src="//upload.wikimedia.org/wikipedia/commons/thumb/8/81/Wikimedia-logo.svg/48px-Wikimedia-logo.svg.png" width="24px" title="Navigate to Continuous integration home"></a>
		<ul class="nav">
			<li class="divider-vertical"></li>
			<li><a href="<?php echo $rootPathHtml; ?>/dashboard/">Dashboard</a></li>
			<li><a href="https://gerrit.wikimedia.org/r/">Gerrit</a></li>
			<li><a href="https://integration.wikimedia.org/ci/">Jenkins</a></li>
			<li><a href="<?php echo $rootPathHtml; ?>/nightly/">Nightly</a></li>
			<li><a href="<?php echo $rootPathHtml; ?>/zuul/">Zuul</a></li>
		</ul>
		</div>
	</div>
</div>
<div class="page-wrap">
	<div class="container">
		<div class="page-header">
			<h2>
				<a href="//www.wikimedia.org/">
				<img src="//upload.wikimedia.org/wikipedia/commons/thumb/1/12/Wikimedia_logo_text_RGB.svg/200px-Wikimedia_logo_text_RGB.svg.png"
					width="100" height="92" title="Visit Wikimedia.org" alt="Wikimedia Foundation logo" class="logo">
				</a>
				<?php echo htmlentities( $this->pageName ); ?>
			</h2>
		</div>
<?php
	echo "\t\t";
	$content = str_replace( '{{ROOT}}', $rootPathHtml, $this->content );
	echo implode( "\n\t\t", explode( "\n", $content ) ) . "\n";
?>
	</div><!-- /.container -->
<?php if ( $this->hasFooter ) { echo "\t<div class=\"push\"></div>\n"; } ?>
</div><!-- /.page-wrap -->
<?php if ( $this->hasFooter ) { ?>
<div class="footer">
	<div class="container">
		<p class="muted credit">
			Questions? Comments? Concerns?<br>
			Contact <em>^demon</em>, <em>Krinkle</em> or <em>hashar</em> on <a href="irc://irc.freenode.net/#wikimedia-dev">#wikimedia-dev</a>.
		</p>
	</div>
</div><!-- /.footer -->
<?php } ?>
<script src="//bits.wikimedia.org/www.mediawiki.org/load.php?debug=false&amp;modules=jquery&amp;only=scripts&amp;raw=1"></script>
<script src="<?php echo $bootstrapPathHtml; ?>/js/bootstrap.min.js"></script>
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
