<?php
function wfParseRespHeaders( $lines ) {
	$headers = [];
	foreach ( $lines as $line ) {
		// Skip HTTP version and multi-line values
		if ( preg_match( "#^([^:]*):[\t ]*(.*)#", $line, $match ) ) {
			$headers[strtolower( $match[1] )] = $match[2];
		}
	}
	return $headers;
}

function wfFaviconErrorText( $text ) {
	header( 'HTTP/1.1 500 Internal Server Error' );
	header( 'Content-Type: text/html; charset=utf-8' );
	echo "<!DOCTYPE html>\n<html>\n<p>" . htmlspecialchars( $text ) . "</p>\n</html>\n";
	exit;
}

function wfStreamFavicon( $url ) {
	$content = file_get_contents( $url );
	if ( !$content ) {
		wfFaviconErrorText( "Failed to fetch url: $url" );
	}
	$resp = wfParseRespHeaders( $http_response_header );
	if ( !isset( $resp['content-type'] ) ) {
		wfFaviconErrorText( "Missing Content-Type header on url: $url" );
	}
	header( 'Content-Length: ' . strlen( $content ) );
	header( 'Content-Type: ' . $resp['content-type'] );
	header( 'Cache-Control: public' );
	header( 'Expires: ' . gmdate( 'r', time() + 86400 ) );
	echo $content;
	exit;
}

wfStreamFavicon( 'https://www.wikimedia.org/static/favicon/wmf.ico' );
