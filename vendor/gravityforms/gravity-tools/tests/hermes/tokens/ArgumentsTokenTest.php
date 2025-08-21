<?php

namespace hermes\tokens;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Arguments_Token;
use PHPUnit\Framework\TestCase;

class ArgumentsTokenTest extends TestCase {

	/**
	 * @dataProvider queryStringProvider
	 *
	 * @param $query_string
	 * @param $expected
	 *
	 * @return void
	 */
	public function testQueryStringParsesCorrectly( $query_string, $expected ) {
		$token = new Arguments_Token( $query_string );

		$this->assertEquals( $expected, $token->items() );
	}

	public function queryStringProvider() {
		return array(

			// Single argument with standard equal operator
			array(
				'id: 5',
				array(
					array(
						'key'        => 'id',
						'comparator' => '=',
						'value'      => '5',
					)
				)
			),

			// Multiple arguments with equal operators
			array(
				'id: 5, date: 2000-01-01',
				array(
					array(
						'key'        => 'id',
						'comparator' => '=',
						'value'      => '5',
					),
					array(
						'key'        => 'date',
						'comparator' => '=',
						'value'      => '2000-01-01',
					)
				)
			),

			// Single argument with lt operator
			array(
				'id_lt: 5',
				array(
					array(
						'key'        => 'id',
						'comparator' => '<',
						'value'      => '5',
					)
				)
			),

			// Single argument with gt operator
			array(
				'id_gt: 5',
				array(
					array(
						'key'        => 'id',
						'comparator' => '>',
						'value'      => '5',
					)
				)
			),

			// Single argument with lte operator
			array(
				'id_lte: 5',
				array(
					array(
						'key'        => 'id',
						'comparator' => '<=',
						'value'      => '5',
					)
				)
			),

			// Single argument with gte operator
			array(
				'id_gte: 5',
				array(
					array(
						'key'        => 'id',
						'comparator' => '>=',
						'value'      => '5',
					)
				)
			),

			// Single argument with ne operator
			array(
				'id_ne: 5',
				array(
					array(
						'key'        => 'id',
						'comparator' => '!=',
						'value'      => '5',
					)
				)
			),

			// Multiple arguments with mixed operators
			array(
				'id: 5, foo_lt: 10, bar_gt: 20, bash_lte: 30, bing_gte: 40, bazinga_ne: 50',
				array(
					array(
						'key'        => 'id',
						'comparator' => '=',
						'value'      => '5',
					),
					array(
						'key'        => 'foo',
						'comparator' => '<',
						'value'      => '10',
					),
					array(
						'key'        => 'bar',
						'comparator' => '>',
						'value'      => '20',
					),
					array(
						'key'        => 'bash',
						'comparator' => '<=',
						'value'      => '30',
					),
					array(
						'key'        => 'bing',
						'comparator' => '>=',
						'value'      => '40',
					),
					array(
						'key'        => 'bazinga',
						'comparator' => '!=',
						'value'      => '50',
					),
				),
			),
		);
	}

}