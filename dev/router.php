<?php
/**
 * Router for the php cli-server built-in webserver.
 *
 * This is not meant to run in production.
 *
 * Inspired by MediaWiki maintenance/dev/includes/router.php
 */

if ( PHP_SAPI !== 'cli-server' ) {
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
		// @phan-suppress-next-line SecurityCheck-PathTraversal
		require_once $published_file . '/index.php';
		return true;
	}
	// Simulate Apache `RewriteRule .* dir.php`
	//
	// When on the docserver/index/ page, and following the link to
	// any of the generated doc directories, it should list the contents
	// of that directory. (e.g. click on "multisubdir").
	//
	// Without the REDIRECT_URL assignment, Page::getRequestPath()
	// would instead interpret it as being on the coverage index.
	$_SERVER['REDIRECT_URL'] = $_SERVER['REQUEST_URI'];
	require_once __DIR__ . '/../org/wikimedia/doc/dir.php';
	return true;
}

if ( pathinfo( $published_file, PATHINFO_EXTENSION ) === 'php' ) {
	// Execute .php file
	// @phan-suppress-next-line SecurityCheck-PathTraversal
	require_once $published_file;
} else {
	// Pass through the rest
	readfile( $published_file );
}

return true;
