<?php
require_once __DIR__ . '/../../../shared/IntegrationPage.php';

$p = IntegrationPage::newIndex();
$p->setRootDir( __DIR__ );

$p->setDir( __DIR__ );
$p->addHtmlFile( 'default.html' );

$p->flush();
