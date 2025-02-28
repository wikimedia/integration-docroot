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

// Prefer published files under $WMF_DOC_PATH
// But, if we're at the "/" root, render the normal index,
// nor the dir.php index.
$base = basename( $_SERVER['REQUEST_URI'] );
$published_file = getenv( 'WMF_DOC_PATH' ) . $_SERVER['REQUEST_URI'];
if ( $base !== '' && is_readable( $published_file ) ) {
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
		// If we're in a directory that exists in both the web app
		// and the doc path, the doc path takes precedence (handled
		// above). If the doc path has no index file, but the web app
		// does, then we let the web app render it.
		// This is used for the doc.wikimedia.org/cover/
		$app_file = realpath( $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'] );
		if ( is_readable( $app_file . '/index.php' ) ) {
			// @phan-suppress-next-line SecurityCheck-PathTraversal
			require_once $app_file . '/index.php';
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
}

$app_file = realpath( $_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'] );
if ( $app_file !== false && is_readable( $app_file ) && is_file( $app_file ) ) {
	$ext = pathinfo( $app_file, PATHINFO_EXTENSION );
	if ( $ext == 'php' ) {
		// Let built-in server handle script execution.
		return false;
	} else {
		// Serve static file with appropriate Content-Type headers.
		$mimes = [
			'css' => 'text/css',
			'html' => 'text/html',
			'js' => 'text/javascript',
			'json' => 'application/json',
			'svg' => 'image/svg+xml',
		];
		$mime = $mimes[$ext] ?? mime_content_type( $app_file ) ?: 'text/plain';
		if ( strpos( $mime, 'text/' ) === 0 ) {
			$mime .= '; charset=UTF-8';
		}
		$content = file_get_contents( $app_file );
		if ( $content === false ) {
			http_response_code( 503 );
			print( 'Failed to get file content' );
			return true;
		}

		$acceptGzip = preg_match( '/\bgzip\b/', $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '' );
		if ( $acceptGzip && preg_match( '/text|json|xml/', $mime ) ) {
			$content = gzencode( $content, 9 );
			if ( $content === false ) {
				http_response_code( 503 );
				print( 'Failed to gzip content' );
				return true;
			}
			header( 'Content-Encoding: gzip' );
		}
		header( 'Vary: Accept-Encoding' );
		header( "Content-Type: $mime" );
		header( 'Content-Length: ' . strlen( $content ) );
		// For /zuul/status-basic-sample.json
		if ( $ext == 'json' ) {
			header( 'Access-Control-Allow-Origin: *' );
			// Retrieving the Zuul status is done with Cache-Control: no-store
			// Gerrit FrontEnd development adds X-TEST-ORIGIN: gerrit-fe-dev-helper
			header( 'Access-Control-Allow-Headers: Cache-Control, X-TEST-ORIGIN' );
		}
		// @phan-suppress-next-line SecurityCheck-XSS
		echo $content;
		return true;
	}
}

return true;
