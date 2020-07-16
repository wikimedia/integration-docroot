<?php

class WmuiPageBase extends Page {
	protected $org = 'Wikimedia';
	protected $site = '';
	protected $caption = '';
	protected $stylesheets = [
		'/lib/wikimedia-ui-base-0.16.0.css',
		'/lib/wmui-page.css',
	];

	protected function getFooterItems() {
		// Stub
		return [];
	}

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
	<?php
	if ( $this->caption ) {
		echo '<span class="wm-header-caption">' . htmlentities( $this->caption ) . '</span>';
	}
	?>
</div></header>
<main role="main"><div class="wm-container">
<nav class="wm-site-nav"><ul class="wm-nav">
<?php
	$cur = $this->getUrlPath();
	foreach ( $this->getNavItems() as $href => $text ) {
		$isActive = $this->isNavActive( $href );
		$subItems = $isActive ? $this->getSubnavItems() : [];

		$attr = $isActive ? ' class="wm-nav-item-active"' : '';
		echo "<li>" . '<a href="' . htmlspecialchars( $href ) . '"' . $attr . '>' . htmlspecialchars( $text ) . '</a>';
		if ( $subItems ) {
			echo '<ul>';
			foreach ( $subItems as $subHref => $subText ) {
				echo '<li><a href="' . htmlspecialchars( $subHref ) . '">' . htmlspecialchars( $subText ) . '</a>';
			}
			echo '</ul>';
		}
		echo '</li>';
	};
?>
</ul></nav>
<article><?php
	if ( $this->pageName ) {
		echo '<h1>' . htmlentities( $this->pageName ) . '</h1>';
	}
	$this->renderContent();
?></article>
</div></main>
<footer role="contentinfo"><div class="wm-container">
	<nav role="navigation"><ul>
<?php
	foreach ( $this->getFooterItems() as $href => $text ) {
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
