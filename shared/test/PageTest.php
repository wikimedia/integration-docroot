<?php

class PageTest extends PHPUnit\Framework\TestCase {
	private $scriptName;

	public function setUp(): void {
		unset( $_SERVER['REDIRECT_URL'] );
		unset( $_SERVER['DOCUMENT_ROOT'] );
		putenv( 'WMF_DOC_PATH' );
		$this->scriptName = $_SERVER['SCRIPT_NAME'];
	}

	public function tearDown(): void {
		unset( $_SERVER['REDIRECT_URL'] );
		unset( $_SERVER['DOCUMENT_ROOT'] );
		putenv( 'WMF_DOC_PATH' );
		$_SERVER['SCRIPT_NAME'] = $this->scriptName;
	}

	/**
	 * @dataProvider resolvePathCases
	 */
	public function testResolvePath( $expected, $base, $path ) {
		$this->assertEquals( $expected, Page::resolvePath( $base, $path ) );
	}

	public static function resolvePathCases() {
		$fixture = __DIR__ . '/fixture';
		return [
			// $expected, $base, $path
			'root is root in root' => [ '/', '/', '/' ],
			'file in base' => [ $fixture . '/foo', $fixture, '/foo' ],
			'validates symlink' => [ $fixture . '/foo', $fixture, '/symlink' ],
			'supports base being a symlink' => [
				$fixture . '/foo', $fixture . '/symlink', '/' ],
			'reject ..' => [ false, $fixture, '/..' ],
			'reject ../' => [ false, $fixture, '/../' ],
			'reject ./..' => [ false, $fixture, './..' ],
		];
	}

	public function testRequestPathWithHomepage() {
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/fixture';
		$page = Page::newIndex();
		$this->assertEquals(
			'/',
			$page->getUrlPath()
		);
		$this->assertEquals(
			__DIR__ . '/fixture/',
			$page->getDir()
		);
	}

	public function testRequestPathWithSubpage() {
		$_SERVER['SCRIPT_NAME'] = '/foo/index.php';
		$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/fixture';
		$page = Page::newIndex();
		$this->assertEquals(
			'/foo/',
			$page->getUrlPath()
		);
		$this->assertEquals(
			__DIR__ . '/fixture/foo/',
			$page->getDir()
		);
	}

	public function testRequestPathWithDirIndex() {
		$_SERVER['REDIRECT_URL'] = '/foo/';
		$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/fixture';
		$page = Page::newIndex();
		$this->assertEquals(
			'/foo/',
			$page->getUrlPath()
		);
		$this->assertEquals(
			__DIR__ . '/fixture/foo/',
			$page->getDir()
		);
	}

	public function testRequestPathSupportsSymlinkDocroot() {
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/fixture/symlink';
		$page = Page::newIndex();
		$this->assertEquals(
			'/',
			$page->getUrlPath()
		);
		$this->assertEquals(
			__DIR__ . '/fixture/foo/',
			$page->getDir()
		);
	}

	public function testFallbackToDocPath() {
		$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/fixture/doesnotexit444';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		putenv( 'WMF_DOC_PATH=' . __DIR__ . '/fixture/docpath' );
		$page = Page::newIndex();
		$this->assertEquals(
			'/',
			$page->getUrlPath()
		);
		$this->assertEquals(
			__DIR__ . '/fixture/docpath/',
			$page->getDir()
		);
	}
}
