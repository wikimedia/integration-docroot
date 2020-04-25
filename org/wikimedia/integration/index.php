<?php
require_once __DIR__ . '/../../../shared/autoload.php';

$p = IntegrationPage::newIndex();
$p->addHtmlFile( __DIR__ . '/default.html' );

$p->flush();
