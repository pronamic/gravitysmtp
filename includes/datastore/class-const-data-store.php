<?php

namespace Gravity_Forms\Gravity_SMTP\Data_Store;

class Const_Data_Store implements Data_Store {

	protected $fallbacks = array(
		'license_key' => 'GF_LICENSE_KEY',
	);

	public function get( $setting_name, $connector ) {
		$connector = is_null( $connector ) ? '' : $connector;
		$const_name = sprintf( 'GRAVITYSMTP_%s_%s', strtoupper( $connector ), strtoupper( $setting_name ) );

		if ( defined( $const_name ) ) {
			return constant( $const_name );
		}

		return null;
	}

	public function save( $setting_name, $value, $connector ) {
		return;
	}

	public function get_plugin_const( $setting_name ) {
		$const_name = sprintf( 'GRAVITYSMTP_%s', strtoupper( $setting_name ) );
		$value      = null;

		if ( defined( $const_name ) ) {
			$value = constant( $const_name );
		} elseif ( isset( $this->fallbacks[ $setting_name ] ) && defined( $this->fallbacks[ $setting_name ] ) ) {
			$value = constant( $this->fallbacks[ $setting_name ] );
		}

		// License key constants hold the plain key. Only the hash leaves the site, the same as Gravity Forms.
		if ( 'license_key' === $setting_name && $value ) {
			return md5( trim( $value ) );
		}

		return $value;
	}
}
