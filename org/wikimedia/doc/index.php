<?php
require_once __DIR__ . '/../../../shared/DocPage.php';

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
	}
' );
$p->addHtmlFile( 'default.html' );

$p->flush();
