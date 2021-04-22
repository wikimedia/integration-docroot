<?php

class TwbsPageBase extends Page {
	protected $stylesheets = [
		'/lib/bootstrap/css/bootstrap.min.css',
		'/lib/twbs-page.css',
	];
	protected $scripts = [
		'/lib/jquery.min.js',
	];

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
?><!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
<meta charset="utf-8">
<title><?php
	if ( $this->pageName ) {
		echo htmlentities( $this->pageName . ' - ' . $this->site );
	} else {
		echo htmlentities( $this->site );
	}
?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="/favicon.ico">
<?php
		foreach ( $this->stylesheets as $stylesheet ) {
			echo '<link rel="stylesheet" href="' . htmlspecialchars( $stylesheet ) . '">' . "\n";
		}
		if ( count( $this->embeddedCSS ) ) {
			echo "<style>\n" . implode( "\n", $this->embeddedCSS ) . "\n</style>\n";
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
		echo $this->getNavHtml();
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
		echo $this->content;
?>
	</div><!-- /.container -->
	<div class="push"></div>'
</div><!-- /.page-wrap -->
<div class="footer">
	<div class="container"><div class="row">
		<p class="col-sm-8">
			More information on <a href="https://www.mediawiki.org/wiki/Special:MyLanguage/Continuous_integration">Continuous Integration</a> at www.mediawiki.org.
		</p>
		<p class="col-sm-4 text-right"><a href="https://www.wikimedia.org"><img src="/lib/wikimedia-button.png" srcset="/lib/wikimedia-button-2x.png 2x" width="88" height="31" alt="Wikimedia Foundation"></a></p>
	</div></div>
</div><!-- /.footer -->
<?php
		foreach ( $this->scripts as $script ) {
			echo '<script defer src="' . htmlspecialchars( $script ) . '"></script>' . "\n";
		}
?>
</body>
</html>
<?php
	}
}
