<?php
require_once __DIR__ . '/../../../../shared/autoload.php';

$p = DocIndexPage::newDirIndex( 'Documentation index' );
$p->handleDirIndex( dirname( __DIR__ ), '/' );
$p->flush();
