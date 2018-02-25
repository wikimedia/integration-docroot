<?php
require_once dirname( __DIR__ ) . '/Page.php';

class PageTest extends PHPUnit\Framework\TestCase {

	public static function provideGetPathTo() {
		return [
			// From directory to child
			[ '/foo/bar/', '/foo/bar/baz/', 'baz/' ],
			[ '/foo/bar/', '/foo/bar/baz.txt', 'baz.txt' ],

			// No-op
			[ '/foo/bar/', '/foo/bar/', './' ],
			[ '/foo/bar/', '/foo/bar/', './' ],

			// From root to child
			[ '/', '/foo/', 'foo/' ],
			[ '/', '/foo/bar.txt', 'foo/bar.txt' ],
			[ '/', '/foo/bar/baz.txt', 'foo/bar/baz.txt' ],

			// To sibling
			[ '/foo/bar/', '/foo/baz/', '../baz/' ],
			[ '/foo/bar/', '/foo/baz.txt', '../baz.txt' ],

			// To parent
			[ '/foo/bar/baz/', '/foo/bar/', '../' ],
			[ '/foo/bar/baz', '/foo/bar', './' ],

			// From file to sibling
			[ '/foo/bar.txt', '/foo/baz/', 'baz/' ],
			[ '/foo/bar.txt', '/foo/baz.txt', 'baz.txt' ],

			// From slashless path to sibling
			[ '/foo/bar', '/foo/baz/', 'baz/' ],
			[ '/foo/bar', '/foo/baz.txt', 'baz.txt' ],

			// From slashless path to child with the same name
			[ '/foo/bar', '/foo/bar/baz/', 'bar/baz/' ],
			[ '/foo/bar', '/foo/bar/baz.txt', 'bar/baz.txt' ],
		];
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
