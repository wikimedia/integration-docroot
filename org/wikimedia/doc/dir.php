<?php
require_once __DIR__ . '/../../../shared/DocPage.php';

$p = DocPage::newDirIndex( '', Page::INDEX_PREFIX | Page::INDEX_PARENT_PREFIX | Page::INDEX_ALLOW_SKIP );
$p->setRootDir( __DIR__ );
$p->handleDirIndex();
$p->flush();
