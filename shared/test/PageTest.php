<?php
require_once dirname( __DIR__ ) . '/Page.php';

class PageTest extends PHPUnit_Framework_TestCase {

	public static function provideGetPathTo() {
		return array(
			// From directory to child
			array( '/foo/bar/', '/foo/bar/baz/', 'baz/' ),
			array( '/foo/bar/', '/foo/bar/baz.txt', 'baz.txt' ),

			// No-op
			array( '/foo/bar/', '/foo/bar/', './' ),
			array( '/foo/bar/', '/foo/bar/', './' ),

			// From root to child
			array( '/', '/foo/', 'foo/' ),
			array( '/', '/foo/bar.txt', 'foo/bar.txt' ),
			array( '/', '/foo/bar/baz.txt', 'foo/bar/baz.txt' ),

			// To sibling
			array( '/foo/bar/', '/foo/baz/', '../baz/' ),
			array( '/foo/bar/', '/foo/baz.txt', '../baz.txt' ),

			// To parent
			array( '/foo/bar/baz/', '/foo/bar/', '../' ),
			array( '/foo/bar/baz', '/foo/bar', './' ),

			// From file to sibling
			array( '/foo/bar.txt', '/foo/baz/', 'baz/' ),
			array( '/foo/bar.txt', '/foo/baz.txt', 'baz.txt' ),

			// From slashless path to sibling
			array( '/foo/bar', '/foo/baz/', 'baz/' ),
			array( '/foo/bar', '/foo/baz.txt', 'baz.txt' ),

			// From slashless path to child with the same name
			array( '/foo/bar', '/foo/bar/baz/', 'bar/baz/' ),
			array( '/foo/bar', '/foo/bar/baz.txt', 'bar/baz.txt' ),
		);
	}

	/**
	 * @dataProvider provideGetPathTo
	 */
	public function testGetPathTo( $fromPath, $toPath, $relative ) {
		$this->assertEquals(
			$relative,
			Page::getPathTo( $fromPath, $toPath )
		);
	}
}
