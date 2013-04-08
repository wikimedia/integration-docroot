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
	 * @param string $path
	 */
	public function setBootstrapPath( $path ) {
		$this->bootstrapPath = $path;
	}

	/**
	 * @return string: Path to boostrap (without trailing slash).
	 */
	public function getBootstrapPath() {
		if ( $this->bootstrapPath ) {
			return $this->bootstrapPath;
		}
		$subpath = '';
		if ( $this->rootDir && $this->dir ) {
			// __DIR__ = /docroot/shared/
			$docrootRepoDir = dirname( __DIR__ ) . '/';
			if ( strpos( $this->rootDir, $docrootRepoDir ) === 0 && strpos( $this->dir, $this->rootDir ) === 0 ) {
				$path = $this->dir;
				while ( $path !== $this->rootDir && strpos( $path, $this->rootDir ) === 0 ) {
					$path = dirname( $path );
					$subpath .= '../';
				}
			}
		}
		return $subpath .'bootstrap';
	}

	/**
	 * @param string $cssText
	 */
	public function embedCSS( $cssText ) {
		$this->embeddedCSS[] = trim( $cssText );
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
		$this->embedCSS('
/**
 * Footer
 */

.footer {
	background-color: #f5f5f5;
}

.footer p {
	margin: 20px 0;
}

@media (max-width: 767px) {
	.footer {
		margin-left: -20px;
		margin-right: -20px;
		padding-left: 20px;
		padding-right: 20px;
	}
}

html,
body {
	height: 100%;
}

.page-wrap {
	min-height: 100%;
	height: auto !important;
	height: 100%;
	/* Bump footer up by its height */
	margin: 0 auto -80px auto;
}

/* Fixed height of footer */
.push,
.footer {
	height: 80px;
}');

	}

	$bootstrapPathHtml = htmlspecialchars( $this->getBootstrapPath() );

?><!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
	<meta charset="utf-8">
	<title><?php echo htmlentities( $this->pageName . ' - ' . $this->site ); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" href="//bits.wikimedia.org/favicon/wmf.ico">
	<link rel="stylesheet" href="<?php echo $bootstrapPathHtml; ?>/css/bootstrap.min.css">
	<link rel="stylesheet" href="<?php echo $bootstrapPathHtml; ?>/css/bootstrap-responsive.min.css">
<?php
	if ( count( $this->embeddedCSS ) ) {
		echo "\t<style>\n\t" . implode( "\n\t", explode( "\n", implode( "\n\n\n", $this->embeddedCSS ) ) ) . "\n\t</style>\n";
	}
?>
</head>
<body>
<div class="page-wrap">
	<div class="container">
		<div class="page-header">
			<h2>
				<a href="//www.wikimedia.org/"><img src="//upload.wikimedia.org/wikipedia/commons/thumb/1/12/Wikimedia_logo_text_RGB.svg/240px-Wikimedia_logo_text_RGB.svg.png"
					width="120" height="120" title="Visit Wikimedia.org" alt="Wikimedia Foundation logo" class="logo"></a>
				<?php echo htmlentities( $this->pageName ); ?>
			</h2>
		</div>
<?php echo "\t\t" . implode( "\n\t\t", explode( "\n", $this->content ) ) . "\n"; ?>
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
