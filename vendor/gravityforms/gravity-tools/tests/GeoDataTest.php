<?php

use PHPUnit\Framework\TestCase;
use Gravity_Forms\Gravity_Tools\Utils\GeoData;

class GeoDataTest extends TestCase {

	public function testCountriesDataIsProperlyFormatted() {
		$countries_data = GeoData::countries();
		// Countries data should be an array
		$this->assertTrue( is_array( $countries_data ) );

		// Countries data should contain known keys
		$this->assertTrue( array_key_exists( 'US', $countries_data ) );
		$this->assertTrue( array_key_exists( 'CA', $countries_data ) );

		// Countries data values should be translated
		$this->assertEquals( $countries_data['AU'], 'Australia');
	}

	public function testCountriesDataCanBeTransformed() {
		$transform_callback = function( $items ) {
			foreach( $items as $key => $value ) {
				$items[ $key ] = 'OVERRIDDEN';
			}

			return $items;
		};
		$countries_data = GeoData::countries( false, $transform_callback );

		$this->assertEquals( $countries_data['AF'], 'OVERRIDDEN' );
	}

	public function testDataReturnsJson() {
		$countries_data = GeoData::countries( true );
		$this->assertTrue( is_string( $countries_data ) );
		$this->assertFalse( is_array( $countries_data ) );

		$countries_data = json_decode( $countries_data, true );

		$this->assertEquals( $countries_data['AU'], 'Australia' );
	}

	public function testPhoneDataReturns() {
		$phone_data = GeoData::phone_info();
		$this->assertTrue( is_array( $phone_data ) );
		$first_value = $phone_data[0];

		$this->assertEquals( $first_value['iso'], 'AF' );
		$this->assertEquals( $first_value['calling_code'], '93' );
	}

}
