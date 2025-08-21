<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Enum;

use Gravity_Forms\Gravity_Tools\Utils\Booliesh;

/**
 * Field Type Validation Enum
 *
 * This class provides defined, explicit methods for validating and sanitizing values
 * before storing them in the database.
 *
 * Example usage:
 *
 * $validated_value = Field_Type_Validation_Enum::validate( Field_Type_Validation_Enum::STRING, 'foobar' );
 *
 * Actual validation methods can be called directly as well:
 *
 * $validated_string = Field_Type_Validation_Enum::validate_string( 'foobar' );
 */
class Field_Type_Validation_Enum {

	const STRING  = 'string';
	const INT     = 'int';
	const FLOAT   = 'float';
	const BOOLEAN = 'boolean';
	const DATE    = 'date';
	const OBJECT  = 'object';
	const ARR     = 'array';
	const EMAIL   = 'email';


	/**
	 * Returns an array mapping field types to their validation callbacks;
	 *
	 * @since 1.0
	 *
	 * @return array[]
	 */
	private static function validation_map() {
		return array(
			self::STRING  => array( self::class, 'validate_string' ),
			self::INT     => array( self::class, 'validate_int' ),
			self::FLOAT   => array( self::class, 'validate_float' ),
			self::BOOLEAN => array( self::class, 'validate_bool' ),
			self::DATE    => array( self::class, 'validate_date' ),
			self::OBJECT  => array( self::class, 'validate_object' ),
			self::ARR     => array( self::class, 'validate_array' ),
			self::EMAIL   => array( self::class, 'validate_email' ),
		);
	}

	/**
	 * Validate a given value based on the field type.
	 *
	 * @since 1.0
	 *
	 * @param string $type The field type to be used for validation.
	 * @param mixed $value The actual value to validate.
	 *
	 * @return mixed
	 */
	public static function validate( $type, $value ) {
		if ( ! is_string( $type ) && is_callable( $type ) ) {
			return call_user_func( $type, $value );
		}

		if ( ! array_key_exists( $type, self::validation_map() ) ) {
			$error_string = sprintf( 'Field type %s is not valid.', $type );
			throw new \InvalidArgumentException( $error_string, 480 );
		}

		$validated_value = call_user_func( self::validation_map()[ $type ], $value );

		return $validated_value;
	}

	/**
	 * Validates a string and adds slashes for DB storage.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return string|null
	 */
	public static function validate_string( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		return addslashes( $value );
	}

	/**
	 * Validates an integer.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return int|null
	 */
	public static function validate_int( $value ) {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		return (integer) $value;
	}

	/**
	 * Validates a float.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return float|null
	 */
	public static function validate_float( $value ) {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		return (float) $value;
	}

	/**
	 * Validates a boolean.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool|null
	 */
	public static function validate_bool( $value ) {
		return Booliesh::get( $value, null );
	}

	/**
	 * Validates a date string.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return string|null
	 */
	public static function validate_date( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$check = strtotime( $value );

		if ( ! $check ) {
			return null;
		}

		return $value;
	}

	/**
	 * Validates an object.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return object|null
	 */
	public static function validate_object( $value ) {
		if ( ! is_object( $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * Validates an array.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return array|null
	 */
	public static function validate_array( $value ) {
		if ( ! is_array( $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * Validates an email.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return string|null
	 */
	public static function validate_email( $value ) {
		return filter_var( $value, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE );
	}

}