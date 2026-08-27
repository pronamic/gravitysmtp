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

		if ( defined( $const_name ) ) {
			return constant( $const_name );
		}

		foreach( $this->fallbacks as $fallback_key => $fallback_constant ) {
			if ( $setting_name !== $fallback_key ) {
				continue;
			}

			if ( defined( $fallback_constant ) ) {
				return constant( $fallback_constant );
			}
		}

		return null;
	}
}
