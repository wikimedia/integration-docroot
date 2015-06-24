<?php
function parseRespHeaders( $lines ) {
	$headers = array();
	foreach ( $lines as $line ) {
		// Skip HTTP version and multi-line values
		if ( preg_match( "#^([^:]*):[\t ]*(.*)#", $line, $match ) ) {
			$headers[strtolower( $match[1] )] = $match[2];
		}
	}
	return $headers;
}

function faviconErrorText( $text ) {
	header( 'HTTP/1.1 500 Internal Server Error' );
	header( 'Content-Type: text/html; charset=utf-8' );
	echo "<!DOCTYPE html>\n<html>\n<p>" . htmlspecialchars( $text ) . "</p>\n</html>\n";
	exit;
}

function streamFavicon( $url ) {
	$content = file_get_contents( $url );
	if ( !$content ) {
		faviconErrorText( "Failed to fetch url: $url" );
	}
	$resp = parseRespHeaders( $http_response_header );
	if ( !isset( $resp['content-length'] ) || !isset( $resp['content-length'] ) ) {
		faviconErrorText( "Missing content headers on url: $url" );
	}
	header( 'Content-Length: ' . $resp['content-length'] );
	header( 'Content-Type: ' . $resp['content-type'] );
	header( 'Cache-Control: public' );
	header( 'Expires: ' . gmdate( 'r', time() + 86400 ) );
	echo $content;
	exit;
}

streamFavicon( 'https://www.wikimedia.org/static/favicon/wmf.ico' );
