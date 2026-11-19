<?php

namespace Gravity_Forms\Gravity_SMTP\Telemetry;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_Tools\Telemetry\Telemetry_Processor;

class Telemetry_Background_Processor extends Telemetry_Processor {

	public function send_data( $entries ) {
		// allow overriding the endpoint to use the local or staging environment for testing purposes.
		$endpoint = defined( 'GF_TELEMETRY_ENDPOINT' ) ? GF_TELEMETRY_ENDPOINT : self::TELEMETRY_ENDPOINT;
		$site_url = get_site_url();

		if ( array_key_exists( 'of', $entries ) ) {
			$entries = array( $entries );
		}

		$data = array(
			'license_key_md5' => $this->get_license_key_md5(),
			'site_url'        => $site_url,
			'product'         => 'gravitysmtp',
			'tag'             => 'system_report',
			'data'            => $entries,
		);

		return wp_remote_post(
			$endpoint . 'api/telemetry_data_bulk',
			array(
				'headers'     => array(
					'Content-Type'  => 'application/json',
					'Authorization' => sha1( $site_url ),
				),
				'method'      => 'POST',
				'data_format' => 'body',
				'body'        => json_encode( $data ),
			)
		);
	}

	/**
	 * Get the license key hash in use, or an empty string when there is no key.
	 *
	 * Gravity SMTP and Gravity Forms both store the key as an MD5 hash, so both products report the same value.
	 *
	 * @since 2.3.5
	 *
	 * @return string
	 */
	protected function get_license_key_md5() {
		$key = Gravity_SMTP::container()
			->get( Connector_Service_Provider::DATA_STORE_ROUTER )
			->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_LICENSE_KEY, '' );
		$key = trim( (string) $key );

		return '' !== $key ? $key : Save_Plugin_Settings_Endpoint::get_gforms_license_key_hash();
	}

}