<?php
require_once __DIR__ . '/../../../shared/autoload.php';

$p = DocPage::newDirIndex( '', Page::INDEX_PREFIX | Page::INDEX_PARENT_PREFIX | Page::INDEX_ALLOW_SKIP );
$p->handleDirIndex( $p->getDir(), $p->getUrlPath() );
$p->flush();
