<?php

class WmuiPageBase extends Page {
	protected $org = 'Wikimedia';
	protected $stylesheets = [
		'/lib/wmui-page.css',
	];

	public function flush() {
?><!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
<meta charset="utf-8">
<title><?php
	if ( $this->pageName ) {
		echo htmlentities( "$this->pageName  - $this->org $this->site" );
	} else {
		echo htmlentities( "$this->org $this->site" );
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
<header><div class="wm-container">
	<a role="banner" href="/" title="Navigate to the home page of <?php echo htmlentities( $this->site ); ?>"><em><?php echo htmlentities( $this->org ); ?></em> <?php echo htmlentities( $this->site ); ?></a>
</div></header>
<main role="main"><div class="wm-container">
<?php
	if ( $this->pageName ) {
		echo '<h1>' . htmlentities( $this->pageName ) . '</h1>';
	}
?>
<article><?php $this->renderContent(); ?></article>
</div></main>
<footer role="contentinfo"><div class="wm-container">
	<nav role="navigation"><ul>
<?php
	foreach ( $this->getNavItems() as $href => $text ) {
		echo '<li><a href="' . htmlspecialchars( $href ) . '">' . htmlspecialchars( $text ) . '</a></li>';
	}
?>
	</ul></nav>
	<a class="wm-link--powered" href="https://www.wikimedia.org">A Wikimedia Foundation project</a>
</div></footer>
<?php
	foreach ( $this->scripts as $script ) {
		echo '<script defer src="' . htmlspecialchars( $script ) . '"></script>' . "\n";
	}
?>
</body>
</html>
<?php
	}

	public function renderContent() {
		echo $this->content;
	}
}
