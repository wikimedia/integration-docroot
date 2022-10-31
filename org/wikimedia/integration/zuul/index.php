<?php
require_once __DIR__ . '/../../../../shared/autoload.php';

$p = IntegrationPage::newFromPageName( 'Zuul Status' );
$p->addHtmlFile( __DIR__ . '/default.html' );

$p->addStylesheet( 'styles/zuul.css' );
$p->addScript( '/lib/jquery.min.js' );
$p->addScript( '/lib/mustache/mustache.js' );
$p->addScript( 'jquery.zuul.js' );
$p->addScript( 'init.js' );

$p->flush();
