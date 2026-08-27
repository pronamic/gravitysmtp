<?php

namespace Gravity_Forms\Gravity_SMTP\Enums;

class Zoho_Datacenters_Enum {

	protected static function map() {
		return array(
			__( 'United States', 'gravitysmtp' ) => 'us',
			__( 'Europe', 'gravitysmtp' )        => 'eu',
			__( 'India', 'gravitysmtp' )         => 'in',
			__( 'Australia', 'gravitysmtp' )     => 'au',
			__( 'Japan', 'gravitysmtp' )         => 'jp',
			__( 'Canada', 'gravitysmtp' )        => 'ca',
			__( 'Saudi Arabia', 'gravitysmtp' )  => 'sa',
		);
	}

	protected static function datacenter_to_url() {
		return array(
			'us' => 'https://mail.zoho.com',
			'eu' => 'https://mail.zoho.eu',
			'in' => 'https://mail.zoho.in',
			'au' => 'https://mail.zoho.com.au',
			'jp' => 'https://mail.zoho.jp',
			'ca' => 'https://mail.zoho.ca',
			'sa' => 'https://mail.zoho.sa',
		);
	}

	protected static function datacenter_to_accounts_url() {
		return array(
			'us' => 'https://accounts.zoho.com',
			'eu' => 'https://accounts.zoho.eu',
			'in' => 'https://accounts.zoho.in',
			'au' => 'https://accounts.zoho.com.au',
			'jp' => 'https://accounts.zoho.jp',
			'ca' => 'https://accounts.zoho.ca',
			'sa' => 'https://accounts.zoho.sa',
		);
	}

	public static function url_for_datacenter( $datacenter ) {
		$map = self::datacenter_to_url();

		if ( isset( $map[ $datacenter ] ) ) {
			return $map[ $datacenter ];
		}

		return $map['us'];
	}

	/**
	 * Get the OAuth accounts-server base URL for the given Zoho datacenter.
	 *
	 * @since 2.3.3
	 *
	 * @param string $datacenter
	 *
	 * @return string
	 */
	public static function accounts_url_for_datacenter( $datacenter ) {
		$map = self::datacenter_to_accounts_url();

		if ( isset( $map[ $datacenter ] ) ) {
			return $map[ $datacenter ];
		}

		return $map['us'];
	}

	public static function select_component_options() {
		$values = self::map();
		$return = array();

		foreach ( $values as $label => $key ) {
			$return[] = array(
				'label' => $label,
				'value' => $key
			);
		}

		return $return;
	}

}
