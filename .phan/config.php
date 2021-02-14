<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';
$cfg['directory_list'][] = dirname( __DIR__ ) . '/dev/';
$cfg['directory_list'][] = dirname( __DIR__ ) . '/org/';
$cfg['directory_list'][] = dirname( __DIR__ ) . '/shared/';
$cfg['exclude_analysis_directory_list'][] = dirname( __DIR__ ) . '/shared/test/';
return $cfg;
