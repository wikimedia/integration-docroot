<?php
require_once __DIR__ . '/../../../shared/autoload.php';

$p = DocHomePage::newFromProjects( __DIR__ . '/opensource.yaml' );
$p->flush();
