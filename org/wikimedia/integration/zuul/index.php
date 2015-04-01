<?php
require_once( __DIR__ . '/../../../../shared/IntegrationPage.php' );

$p = IntegrationPage::newFromPageName( 'Zuul Status' );
$p->setDir( __DIR__ );
$p->setRootDir( dirname( __DIR__ ) );

$p->addHtmlFile( 'default.html' );
$p->enableFooter();

$p->addStylesheet( 'main.css' );
$p->addScript( 'status.js' );

$p->flush();
