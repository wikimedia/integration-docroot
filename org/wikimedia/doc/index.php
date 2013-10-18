<?php
require_once __DIR__ . '/../../../shared/DocPage.php';

$p = DocPage::newIndex();
$p->addHtmlFile('default.html');
$p->flush();
