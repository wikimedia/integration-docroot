<?php
require_once __DIR__ . '/../../../shared/IntegrationPage.php';

$p = IntegrationPage::newIndex();
$p->addHtmlFile( 'default.html' );
$p->flush();
