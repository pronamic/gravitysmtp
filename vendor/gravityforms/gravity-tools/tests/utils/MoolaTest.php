<?php

namespace utils;

use PHPUnit\Framework\TestCase;
use Gravity_Forms\Gravity_Tools\Utils\Moola;
use \InvalidArgumentException;

class MoolaTest extends TestCase {

	public function testValidCurrency() {
		$this->expectNotToPerformAssertions();
		new Moola( 100, 'USD' );
	}

	public function testInvalidCurrencyException() {
		$this->expectException( InvalidArgumentException::class );
		new Moola( 100, 'FOO' );
	}

	public function testRawOutput() {
		$moola = new Moola( 1000, 'USD' );

		$this->assertEquals( 1000, $moola->raw_value() );
	}

	/**
	 * @dataProvider displayOutputDataProvider
	 */
	public function testDisplayOutput( $amount, $currency, $precision, $show_currency, $expected ) {
		$moola = new Moola( $amount, $currency );
		$value = $moola->display_value( $precision, $show_currency );

		$this->assertEquals( $expected, $value );
	}

	public function displayOutputDataProvider() {
		return array(
			array(
				1000,
				'USD',
				0,
				false,
				10,
			),

			array(
				1000,
				'JPY',
				0,
				false,
				1000,
			),

			array(
				1234,
				'USD',
				1,
				false,
				12.3,
			),

			array(
				1234,
				'USD',
				2,
				false,
				12.34
			),

			array(
				1234,
				'USD',
				2,
				true,
				'$12.34'
			)
		);
	}

	public function testChangeCurrency() {
		$moola = new Moola( 1000, 'USD' );
		$moola->change_currency( 'JPY' );

		$this->assertEquals( 10, $moola->raw_value() );

		$moola->change_currency( 'USD' );

		$this->assertEquals( 1000, $moola->raw_value() );

		$this->assertEquals( '10', $moola->display_value() );
		$moola->change_currency( 'JPY' );

		$this->assertEquals( '10', $moola->display_value() );
	}

	public function testDisplayValueConversion() {
		$moola = new Moola( '$1000.00', 'USD', false );
		$this->assertEquals( 100000, $moola->raw_value() );
	}

}
