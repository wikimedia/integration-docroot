<?php
require_once __DIR__ . '/../../../../shared/DocPage.php';

$p = DocPage::newDirIndex( 'Coverage' );
$p->setRootDir( dirname( __DIR__ ) );
$p->handleDirIndex();
