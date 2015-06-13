<?php
require_once __DIR__ . '/../../../../shared/IntegrationPage.php';

$p = IntegrationPage::newFromPageName( 'Zuul Status' );
$p->setRootDir( dirname( __DIR__ ) );

$p->setDir( __DIR__ );
$p->addHtmlFile( 'default.html' );
$p->addStylesheet( 'styles/zuul.css' );
$p->addScript( 'jquery-visibility.js' );
$p->addScript( 'jquery.zuul.js' );
$p->addScript( 'zuul.app.js' );
$p->addScript( 'init.js' );

$p->flush();
