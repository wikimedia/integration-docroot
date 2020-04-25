<?php
require_once __DIR__ . '/../../../shared/autoload.php';

$p = DocPage::newIndex();
$p->setRootDir( __DIR__ );

$p->setDir( __DIR__ );
$p->embedCSS( '
	.wm-docindex-list {
		column-width: 25em;
	}
	.wm-docindex-list section {
		min-width: 20em;
		break-inside: avoid;
		/* Ensure margin is contained so that each column starts similarly.
		   Without this, the margin of the first child of each section
		   (e.g. heading) would be transferred to the bottom of a prior
		   column, thus causing later columns to lack their top margin. */
		display: inline-block;
	}
' );
$p->addHtmlFile( 'default.html' );

$p->flush();
