<?php
require_once __DIR__ . '/../../../shared/autoload.php';

$p = DocHomePage::newIndex();

$p->embedCSS( '
	.wm-docindex-list {
		column-count: 3;
		column-width: 20em;
	}
	.wm-docindex-list section {
		/* Ensure margin is contained so that each column starts similarly.
		   Without this, the margin of the first child of each section
		   (e.g. heading) would be transferred to the bottom of a prior
		   column, thus causing later columns to lack their top margin. */
		break-inside: avoid;
	}
	.wm-docindex-list section:first-child h3 {
		margin-top: 0;
	}
' );
$p->addHtmlFile( __DIR__ . '/default.html' );

$p->flush();
