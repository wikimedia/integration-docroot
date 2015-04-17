<?php
require_once __DIR__ . '/../../../../shared/IntegrationPage.php';

$p = IntegrationPage::newDirIndex( 'Coverage' );
$p->setRootDir( dirname( __DIR__ ) );
$p->flush();
