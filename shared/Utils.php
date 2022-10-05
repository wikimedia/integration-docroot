<?php

class Utils {
	/**
	 * krinkle/rms-yaml@0.2.0: A really-made-simple YAML parser.
	 *
	 * - Only string values are allowed (unquoted, single-line).
	 * - Must use 2 spaces for indentation.
	 *
	 * Specifically, no attempt to turn some words (like "no") into bools,
	 * or some version strings into floats (like "1.10").
	 *
	 * @param string $input
	 * @return array
	 * @return-taint tainted
	 */
	public static function parseRmsYaml( $input ) {
		$lines = explode( "\n", $input );
		$root = [];
		$stack = [ &$root ];
		$prev = 0;
		foreach ( $lines as $i => $text ) {
			$line = $i + 1;
			$trimmed = ltrim( $text, ' ' );
			if ( $trimmed === '' || $trimmed[0] === '#' ) {
				continue;
			}
			$indent = strlen( $text ) - strlen( $trimmed );
			if ( $indent % 2 !== 0 ) {
				throw new RuntimeException( "YAML: Odd indentation on line $line." );
			}
			$depth = $indent === 0 ? 0 : ( $indent / 2 );
			if ( $depth < $prev ) {
				// Close current object
				array_splice( $stack, $depth + 1 );
			}
			if ( !array_key_exists( $depth, $stack ) ) {
				throw new RuntimeException( "YAML: Too much indentation on line $line." );
			}
			if ( strpos( $trimmed, ':' ) === false ) {
				throw new RuntimeException( "YAML: Missing colon on line $line." );
			}
			$dest =& $stack[ $depth ];
			if ( $dest === null ) {
				// Promote from null to object
				$dest = [];
			}
			[ $key, $val ] = explode( ':', $trimmed, 2 );
			$val = ltrim( $val, ' ' );
			if ( $val !== '' ) {
				// Add string
				$dest[ $key ] = $val;
			} else {
				// Add null (may get promoted to object)
				$val = null;
				$stack[] = &$val;
				$dest[ $key ] = &$val;
			}
			$prev = $depth;
			unset( $dest, $val );
		}
		return $root;
	}

	private function __construct() {
	}
}
