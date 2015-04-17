<?php
require_once __DIR__ . '/../../../../shared/IntegrationPage.php';

$p = IntegrationPage::newDirIndex( 'Coverage: ', Page::INDEX_PREFIX | Page::INDEX_ALLOW_SKIP );
$p->setRootDir( dirname( __DIR__ ) );
$p->flush();
