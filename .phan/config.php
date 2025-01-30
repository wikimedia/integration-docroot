<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config-library.php';

// We override the directory lists to erase the defaults provided by
// mediawiki-phan-config which are specific to MediaWiki.
$cfg['directory_list'] = [
	dirname( __DIR__ ) . '/dev/',
	dirname( __DIR__ ) . '/org/',
	dirname( __DIR__ ) . '/shared/',
];
$cfg['exclude_analysis_directory_list'] = [
	dirname( __DIR__ ) . '/shared/test/',
];

$cfg['strict_method_checking'] = true;
$cfg['strict_object_checking'] = true;
$cfg['strict_param_checking'] = true;
$cfg['strict_property_checking'] = true;
$cfg['strict_return_checking'] = true;

return $cfg;
