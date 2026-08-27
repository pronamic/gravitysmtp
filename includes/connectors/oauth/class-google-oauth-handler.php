<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Oauth;

class Google_Oauth_Handler extends Oauth_Callback_Handler {

	const STATE_NONCE_ACTION = 'gravitysmtp_google_oauth';

	const ERROR_TRANSIENT_PREFIX = 'gravitysmtp_google_oauth_error_';

	protected $namespace = 'google';

	protected $proxy_auth_path = 'auth/gmail';

	protected function get_token_url() {
		return 'https://oauth2.googleapis.com/token';
	}

	protected function get_token_request_body( $code ) {
		return array(
			'client_id'     => $this->get_client_id(),
			'client_secret' => $this->get_client_secret(),
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => $this->get_return_url( 'settings', false ),
		);
	}

	public function get_refresh_url() {
		return esc_url( 'https://oauth2.googleapis.com/token' );
	}

	protected function get_token_check_url() {
		return 'https://gmail.googleapis.com/gmail/v1/users/me/profile';
	}

	public function get_connection_details() {
		$token    = $this->data->get( 'access_token', $this->namespace );
		$response = wp_remote_get( $this->get_token_check_url(), array( 'headers' => $this->get_auth_headers( $token ) ) );
		$code     = wp_remote_retrieve_response_code( $response );

		if ( (int) $code !== 200 ) {
			return array(
				'email' => __( 'Unable to retrieve associated email.', 'gravitysmtp' ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return array(
			'email' => $body['emailAddress'],
		);
	}

}
