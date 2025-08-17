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

$envDocPath = getenv( 'WMF_DOC_PATH' );
$srvDocroot = $_SERVER['DOCUMENT_ROOT'];
$srvReqUri = $_SERVER['REQUEST_URI'];
$srvScriptName = $_SERVER['SCRIPT_NAME'];

if ( !$envDocPath || !$srvDocroot || !$srvReqUri || !$srvScriptName ) {
	// Let built-in server handle error.
	return false;
}

$published_file = realpath( $envDocPath . $srvReqUri );
// | URL                    | app_file             | app_script
// | ---------------------- | -------------------- | ----------
// | /                      | /root                | /root/index.php
// | /logos/mediawiki.svg   | /logos/mediawiki.svg | /logos/mediawiki.svg
// | /logos/mediawiki.svg?x | false                | /logos/mediawiki.svg
// | /?x=                   | false                | /root/index.php
// | /cover/                | /root/cover          | /root/cover/index.php
// | /cover/?x=             | false                | /root/cover/index.php

// Path based on literal URL (including virtual dir and query string as-is)
$app_file = realpath( $srvDocroot . $srvReqUri );
// The presumed static file or PHP file guessed by PHP (sans query param)
$app_script = realpath( $srvDocroot . $srvScriptName );

// Prefer published files under WMF_DOC_PATH
// Except for the "/" root, which should render the app homepage instead of dir.php index.
if ( $published_file !== false && $published_file !== $envDocPath ) {
	if ( is_dir( $published_file ) ) {
		// Simulate Apache `DirectoryIndex index.html index.php`
		if ( is_file( $published_file . '/index.html' ) ) {
			readfile( $published_file . '/index.html' );
			return true;
		}
		if ( is_file( $published_file . '/index.php' ) ) {
			// @phan-suppress-next-line SecurityCheck-PathTraversal
			require_once $published_file . '/index.php';
			return true;
		}

		// If we're in a directory that exists in both the web app (DOCUMENT_ROOT)
		// and WMF_DOC_PATH, the doc path takes precedence (handled above).
		// If the doc path has no index file, but the web app does,
		// we let the web app render it.
		// This powers https://doc.wikimedia.org/cover/
		if ( $app_file !== false && is_file( $app_file . '/index.php' ) ) {
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
		$_SERVER['REDIRECT_URL'] = $srvReqUri;
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

if ( $app_script !== false && is_file( $app_script ) ) {
	$ext = pathinfo( $app_script, PATHINFO_EXTENSION );
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
		$mime = $mimes[$ext] ?? mime_content_type( $app_script ) ?: 'text/plain';
		if ( str_starts_with( $mime, 'text/' ) ) {
			$mime .= '; charset=UTF-8';
		}
		$content = file_get_contents( $app_script );
		if ( $content === false ) {
			http_response_code( 503 );
			print "Failed to get file content\n";
			return true;
		}

		$acceptGzip = preg_match( '/\bgzip\b/', $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '' );
		if ( $acceptGzip && preg_match( '/text|json|xml/', $mime ) ) {
			$content = gzencode( $content, 9 );
			if ( $content === false ) {
				http_response_code( 503 );
				print "Failed to gzip content\n";
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
		print $content;
		return true;
	}
}

return true;
