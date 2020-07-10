<?php

class PageTest extends PHPUnit\Framework\TestCase {
	private $scriptName;

	public function setUp() {
		unset( $_SERVER['REDIRECT_URL'] );
		unset( $_SERVER['DOCUMENT_ROOT'] );
		$this->scriptName = $_SERVER['SCRIPT_NAME'];
	}

	public function tearDown() {
		unset( $_SERVER['REDIRECT_URL'] );
		unset( $_SERVER['DOCUMENT_ROOT'] );
		$_SERVER['SCRIPT_NAME'] = $this->scriptName;
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
}
