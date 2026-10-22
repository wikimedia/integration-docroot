<?php
/**
 * Generate a static integration homepage and zuul
 */

if ( PHP_SAPI !== 'cli' ) {
	die( "This script can only be run by php's cli sapi." );
}

$pages = [
	"integration" => __DIR__ . '/../org/wikimedia/integration',
	"zuul" => __DIR__ . '/../org/wikimedia/integration/zuul',
];

foreach ( $pages as $page ) {
	ob_start();
	require $page . '/index.php';
	$pageContents = (string)ob_get_clean();
	file_put_contents( $page . '/index.html', $pageContents );
}
