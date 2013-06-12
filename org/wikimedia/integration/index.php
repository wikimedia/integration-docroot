<?php
require_once( __DIR__ . '/../../../shared/IntegrationPage.php' );

$p = IntegrationPage::newFromPageName( 'Continuous integration' );
$p->enableFooter();
$p->addHtmlFile( 'default.html' );
$p->flush();
