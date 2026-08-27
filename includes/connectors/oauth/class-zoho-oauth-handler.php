<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Oauth;

use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Zoho;
use Gravity_Forms\Gravity_SMTP\Enums\Zoho_Datacenters_Enum;

class Zoho_Oauth_Handler extends Oauth_Callback_Handler {

	const STATE_NONCE_ACTION = 'gravitysmtp_zoho_oauth';

	const ERROR_TRANSIENT_PREFIX = 'gravitysmtp_zoho_oauth_error_';

	const ERROR_ACCOUNT_FETCH = 'account_fetch_failed';

	protected $namespace = 'zoho';

	public function get_connection_details() {
		return array(
			'account_id' => $this->data->get( Connector_Zoho::SETTING_ACCOUNT_ID, $this->namespace )
		);
	}

	public function get_scope() {
		return 'ZohoMail.messages.CREATE,ZohoMail.accounts.READ';
	}

	protected function get_token_url() {
		$args = array(
			'grant_type'   => 'authorization_code',
			'redirect_uri' => $this->get_return_url( 'settings', true ),
		);

		return add_query_arg( $args, $this->get_accounts_url() . '/oauth/v2/token' );
	}

	protected function get_token_request_body( $code ) {
		return array(
			'client_id'     => $this->get_client_id(),
			'client_secret' => $this->get_client_secret(),
			'code'          => $code,
		);
	}

	/**
	 * Look up and store the account ID the new connection sends mail from.
	 *
	 * @since 2.3.3
	 *
	 * @param array $response The decoded token endpoint response.
	 *
	 * @return string ERROR_ACCOUNT_FETCH on failure, empty string on success.
	 */
	protected function after_token_exchange( $response ) {
		$accounts_url = $this->get_api_url( 'api/accounts' );
		$headers      = array(
			'Authorization' => 'Zoho-oauthtoken ' . $response[ $this->payload_access_token_name ],
		);

		$request     = wp_remote_get( $accounts_url, array( 'headers' => $headers ) );
		$status_code = (int) wp_remote_retrieve_response_code( $request );
		$data        = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( ! isset( $data['data'][0]['accountId'] ) ) {
			$this->log_callback_error( sprintf( 'Account lookup failed with HTTP status %d.', $status_code ) );

			// Clear the stored tokens so an unusable connection never shows as connected.
			$this->store_access_token( '' );
			$this->store_refresh_token( '' );

			return self::ERROR_ACCOUNT_FETCH;
		}

		$this->data->save( Connector_Zoho::SETTING_ACCOUNT_ID, $data['data'][0]['accountId'], $this->namespace );

		return '';
	}

	protected function request_refreshed_token( $refresh_token ) {
		$args                 = $this->get_refresh_request_body( $refresh_token );
		$args['redirect_uri'] = $this->get_return_url( 'settings', true );

		// Zoho expects the refresh parameters in the query string.
		return wp_remote_post( add_query_arg( $args, $this->get_refresh_url() ), array( 'body' => array() ) );
	}

	public function get_refresh_url() {
		return esc_url( $this->get_accounts_url() . '/oauth/v2/token' );
	}

	protected function get_token_check_url() {
		return $this->get_api_url( 'api/accounts' );
	}

	protected function get_auth_headers( $token ) {
		return array(
			'Authorization' => 'Zoho-oauthtoken ' . $token,
			'Content-type'  => 'application/json',
			'Accept'        => 'application/json',
		);
	}

	/**
	 * Get the OAuth accounts-server base URL for the account's configured Zoho datacenter.
	 *
	 * @since 2.3.3
	 *
	 * @return string
	 */
	private function get_accounts_url() {
		$data_center_location = $this->data->get( Connector_Zoho::SETTING_DATA_CENTER_REGION, $this->namespace );

		if ( empty( $data_center_location ) ) {
			$data_center_location = 'us';
		}

		return untrailingslashit( Zoho_Datacenters_Enum::accounts_url_for_datacenter( $data_center_location ) );
	}

	private function get_api_url( $endpoint ) {
		$data_center_location = $this->data->get( Connector_Zoho::SETTING_DATA_CENTER_REGION, $this->namespace );

		if ( empty( $data_center_location ) ) {
			$data_center_location = 'us';
		}

		$base = Zoho_Datacenters_Enum::url_for_datacenter( $data_center_location );

		return trailingslashit( $base ) . $endpoint;
	}

}
