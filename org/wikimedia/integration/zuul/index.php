<?php
require_once __DIR__ . '/../../../../shared/autoload.php';

$p = IntegrationZuulPage::newFromPageName( 'Zuul Status' );
$p->addHtmlFile( __DIR__ . '/default.html' );

$p->addStylesheet( 'styles/zuul.css' );
$p->addScript( 'jquery.zuul.js' );
$p->addScript( 'zuul.app.js' );
$p->addScript( 'init.js' );

$p->flush();
