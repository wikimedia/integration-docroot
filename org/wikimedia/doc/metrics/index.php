<?php
require_once __DIR__ . '/../../../../shared/autoload.php';

$p = DocPage::newDirIndex( 'Code metrics' );
$p->addHtmlFile( __DIR__ . '/default.html' );

$p->flush();
