<?php

class UtilsTest extends PHPUnit\Framework\TestCase {
	public function testParseRmsYaml() {
		$this->assertSame(
			[],
			Utils::parseRmsYaml( '' )
		);
		$this->assertSame(
			[ 'foo' => 'bar' ],
			Utils::parseRmsYaml( "foo: bar" )
		);
		$this->assertSame(
			[ 'foo' => [ 'bar' => 'baz' ] ],
			Utils::parseRmsYaml( "foo:\n  bar: baz" )
		);
	}
}
