<?php
/**
 * Router for the php cli-server built-in webserver.
 *
 * This is not meant to run in production.
 *
 * Inspired by MediaWiki maintenance/dev/includes/router.php
 */

if ( PHP_SAPI != 'cli-server' ) {
	die( "This script can only be run by php's cli-server sapi." );
}

if ( !isset( $_SERVER['SCRIPT_FILENAME'] ) ) {
	// Let built-in server handle error.
	return false;
}

$app_file = realpath( $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'] );
if ( is_readable( $app_file ) ) {
	// The requested file belong to our PHP application.
	return false;
}

// Fallback to published files under $WMF_DOC_PATH
$published_file = realpath( getenv( 'WMF_DOC_PATH' ) . $_SERVER['REQUEST_URI'] );
if ( !is_readable( $published_file ) ) {
	return false;
}

if ( is_dir( $published_file ) ) {
	// Simulate Apache `DirectoryIndex index.html index.php
	if ( is_readable( $published_file . '/index.html' ) ) {
		readfile( $published_file . '/index.html' );
		return true;
	}
	if ( is_readable( $published_file . '/index.php' ) ) {
		require_once $published_file . '/index.php';
		return true;
	}
	// Simulate Apache `RewriteRule .* dir.php`
	require_once './org/wikimedia/doc/dir.php';
}

if ( pathinfo( $published_file, PATHINFO_EXTENSION ) === 'php' ) {
	// Execute .php file
	require_once $published_file;
	return true;
} else {
	// Pass through the rest
	readfile( $published_file );
	return true;
}
