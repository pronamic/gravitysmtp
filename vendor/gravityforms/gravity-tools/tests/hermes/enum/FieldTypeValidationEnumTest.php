<?php

use PHPUnit\Framework\TestCase;

use Gravity_Forms\Gravity_Tools\Hermes\Enum\Field_Type_Validation_Enum;

class FieldTypeValidationEnumTest extends TestCase {

	/**
	 * @dataProvider fieldValuesProvider
	 *
	 * @param $field_type
	 * @param $value
	 * @param $expected
	 *
	 * @return void
	 */
	public function testFieldsAreValidated( $field_type, $value, $expected ) {
		$result = Field_Type_Validation_Enum::validate( $field_type, $value );

		$this->assertEquals( $expected, $result );
	}


	/**
	 * Returns an array with the following values:
	 *
	 * - Field type to check
	 * - Value to validate
	 * - Expected result
	 *
	 *
	 * @return array[]
	 */
	public function fieldValuesProvider() {
		return array(

			// Basic string
			array(
				Field_Type_Validation_Enum::STRING,
				'foobar',
				'foobar'
			),

			// String that needs to be slashified
			array(
				Field_Type_Validation_Enum::STRING,
				"'s-Hertogenbosch",
				"\'s-Hertogenbosch",
			),

			// Non-string that should return null
			array(
				Field_Type_Validation_Enum::STRING,
				array(),
				null,
			),

			// Non-string that should return null
			array(
				Field_Type_Validation_Enum::STRING,
				12,
				null,
			),

			// Basic integer
			array(
				Field_Type_Validation_Enum::INT,
				5,
				5,
			),

			// Basic string integer
			array(
				Field_Type_Validation_Enum::INT,
				'5',
				5,
			),

			// Float cast as integer
			array(
				Field_Type_Validation_Enum::INT,
				5.0,
				5,
			),

			// Non-numeric string that should return null
			array(
				Field_Type_Validation_Enum::INT,
				'five',
				null,
			),

			// Non-integer that should return null
			array(
				Field_Type_Validation_Enum::INT,
				array(),
				null,
			),

			// Basic float
			array(
				Field_Type_Validation_Enum::FLOAT,
				5.25,
				5.25
			),

			// String masquerading as float
			array(
				Field_Type_Validation_Enum::FLOAT,
				'5.25',
				5.25
			),

			// Invalid float
			array(
				Field_Type_Validation_Enum::FLOAT,
				'5.2f',
				null
			),

			// Truly unhinged float
			array(
				Field_Type_Validation_Enum::FLOAT,
				array( 'FLOAT' ),
				null
			),

			// Boolean true
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				true,
				true
			),

			// Boolean true
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				'true',
				true
			),

			// Boolean true
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				1,
				true
			),

			// Boolean true
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				'yes',
				true
			),

			// Boolean true
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				'1',
				true
			),

			// Boolean true
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				'on',
				true
			),

			// Boolean false
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				false,
				false
			),

			// Boolean false
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				'false',
				false
			),

			// Boolean false
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				0,
				false
			),

			// Boolean false
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				'no',
				false
			),

			// Boolean false
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				'0',
				false
			),

			// Boolean false
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				'off',
				false
			),

			// Boolean null
			array(
				Field_Type_Validation_Enum::BOOLEAN,
				new \stdClass(),
				null
			),

			// Y/m/d
			array(
				Field_Type_Validation_Enum::DATE,
				'2000/12/01',
				'2000/12/01'
			),

			// m-d-Y
			array(
				Field_Type_Validation_Enum::DATE,
				'12-01-2000',
				'12-01-2000'
			),

			// Timestamp
			array(
				Field_Type_Validation_Enum::DATE,
				'2000-12-01 12:00:00',
				'2000-12-01 12:00:00'
			),

			// Invalid m-d-y
			array(
				Field_Type_Validation_Enum::DATE,
				'24-24-2000',
				null
			),

			// Not a date
			array(
				Field_Type_Validation_Enum::DATE,
				array(),
				null
			),

			// Simple email
			array(
				Field_Type_Validation_Enum::EMAIL,
				'foo@bar.com',
				'foo@bar.com',
			),

			// advanced email
			array(
				Field_Type_Validation_Enum::EMAIL,
				'foo+stu_ff.here@bar.com',
				'foo+stu_ff.here@bar.com',
			),

			// Bad email
			array(
				Field_Type_Validation_Enum::EMAIL,
				'foo@bar',
				null,
			),

			// Another bad email
			array(
				Field_Type_Validation_Enum::EMAIL,
				'foo here@bar.com',
				null,
			),

			// Custom Callback
			array(
				function( $value ) {
					return 'hey';
				},
				'foo here@bar.com',
				'hey',
			),

			// Custom array callback
			array(
				array( $this, 'custom_validation_callback' ),
				'foo',
				'got foo',
			),

			// Custom array callback
			array(
				array( $this, 'custom_validation_callback' ),
				'bar',
				'got bar',
			),

			// Custom array callback
			array(
				array( $this, 'custom_validation_callback' ),
				'bazinga',
				null,
			),
		);
	}

	public function custom_validation_callback( $value ) {
		if ( $value === 'foo' ) {
			return 'got foo';
		}

		if ( $value === 'bar' ) {
			return 'got bar';
		}

		return null;
	}

}