<?php

namespace hermes\tokens;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Arguments_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Field_Token;
use PHPUnit\Framework\TestCase;

class FieldTokenTest extends TestCase {


	/**
	 * @dataProvider arrayArgsProvider
	 *
	 * @param $matches
	 * @param $marks
	 * @param $arguments
	 * @param $expected_name
	 * @param $expected_alias
	 * @param $expected_arguments
	 *
	 * @return void
	 */
	public function testArrayArgsParseCorrectly( $matches, $marks, $arguments, $expected_name, $expected_alias, $expected_arguments ) {
		$token = new Field_Token( $matches, $marks, $arguments );

		$this->assertEquals( $expected_name, $token->name() );
		$this->assertEquals( $expected_alias, $token->alias() );
		$this->assertEquals( $expected_arguments, $token->children() );
	}

	public function arrayArgsProvider() {
		return array(
			array(
				array(),
				array(),
				array(
					'name'      => 'foo',
					'alias'     => 'foo',
					'arguments' => array(),
				),
				'foo',
				'foo',
				array(),
			),

			array(
				array(),
				array(),
				array(
					'name'      => 'foo',
					'alias'     => 'bash',
					'arguments' => array(),
				),
				'foo',
				'bash',
				array(),
			),

			array(
				array(),
				array(),
				array(
					'name'      => 'foo',
					'alias'     => 'bash',
					'arguments' => array(
						'foo' => 'bar',
					),
				),
				'foo',
				'bash',
				array(
					'foo' => 'bar',
				),
			),

			array(
				array( 'foo' => 'bar' ),
				array( 'bing' => 'bash' ),
				array(
					'name'      => 'foo',
					'alias'     => 'foo',
					'arguments' => array(),
				),
				'foo',
				'foo',
				array(),
			)
		);
	}

}