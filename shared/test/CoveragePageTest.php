<?php

/**
 * @covers CoveragePage
 */
class CoveragePageTest extends PHPUnit\Framework\TestCase {

	public static function provideParseClover() {
		return [
			'Empty file' => [
				'',
				false,
			],
			'Missing project root' => [
				'<example></example>',
				100.0,
			],
			'Sample file' => [
				// From wikimedia/wrappedstring
				'<?xml version="1.0" encoding="UTF-8"?>'
					. '<coverage generated="1"><project timestamp="2">'
					. '<metrics files="2" loc="232" ncloc="107" classes="2" methods="10" coveredmethods="8" conditionals="0" coveredconditionals="0" statements="52" coveredstatements="48" elements="62" coveredelements="56"/>'
					. '</project></coverage>',
				90.0
			],
		];
	}

	/**
	 * @dataProvider provideParseClover
	 */
	public function testParseClover( $input, $expected ) {
		$page = CoveragePage::newFromPageName( 'Example' );

		if ( $expected === false ) {
			$this->expectException( Exception::class );
		}

		$this->assertSame(
			$expected,
			$page->parseClover( $input )
		);
	}

	public static function provideParseLcov() {
		return [
			'Empty file' => [
				'',
				0.0,
			],
			'Sample file' => [
				// Simplified from mediawiki/php/excimer
				'TN:
SF:/mediawiki-php-excimer/excimer.c
LF:495
LH:402
TN:
SF:/mediawiki-php-excimer/excimer_log.c
LF:325
LH:315
TN:
SF:/mediawiki-php-excimer/excimer_mutex.c
LF:22
LH:12
TN:
SF:/mediawiki-php-excimer/excimer_timer.c
LF:128
LH:106
TN:
SF:/mediawiki-php-excimer/php_excimer.h
LF:4
LH:3',
				86.0
			],
		];
	}

	/**
	 * @dataProvider provideParseLcov
	 */
	public function testParseLcov( $input, $expected ) {
		$page = CoveragePage::newFromPageName( 'Example' );

		$this->assertSame(
			$expected,
			$page->parseLcov( $input )
		);
	}
}
