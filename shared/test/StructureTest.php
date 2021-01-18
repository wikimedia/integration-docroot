<?php

class StructureTest extends PHPUnit\Framework\TestCase {
	public function testOpensourceYaml() {
		$data = Utils::parseRmsYaml(
			file_get_contents( __DIR__ . '/../../org/wikimedia/doc/opensource.yaml' )
		);

		$knownKeys = [
			'tagline',
			'lang',
			'homepage',
			'links',
		];

		foreach ( $data as $section => $projects ) {
			$this->assertIsArray( $projects, "Value of $section" );
			foreach ( $projects as $title => $project ) {
				$this->assertTrue(
					isset( $project['homepage'] ) || (
						isset( $project['links'] ) && count( $project['links'] ) > 0
					),
					"Homepage or other link required for $title"
				);

				$unknown = array_diff( array_keys( $project ), $knownKeys );
				$this->assertEquals( $unknown, [], "Illegal keys for $title" );
			}
		}
	}
}
