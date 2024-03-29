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
				[ 'percent' => 100.0 ],
			],
			'Sample file' => [
				// From wikimedia/wrappedstring
				'<?xml version="1.0" encoding="UTF-8"?>'
					. '<coverage generated="1"><project timestamp="2">'
					. '<metrics files="2" loc="232" ncloc="107" classes="2" methods="10" coveredmethods="8" conditionals="0" coveredconditionals="0" statements="52" coveredstatements="48" elements="62" coveredelements="56"/>'
					. '</project></coverage>',
				[ 'percent' => 90.32 ]
			],
		];
	}

	/**
	 * @dataProvider provideParseClover
	 */
	public function testParseClover( $input, $expected ) {
		$class = new ReflectionClass( CoveragePage::class );
		$method = $class->getMethod( 'parseClover' );
		$method->setAccessible( true );

		$page = CoveragePage::newFromPageName( 'Example' );

		if ( $expected === false ) {
			$this->expectException( Exception::class );
		}

		$this->assertSame(
			$expected,
			$method->invokeArgs( $page, [ $input ] )
		);
	}
}
