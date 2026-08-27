<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Oauth;

class Microsoft_Oauth_Handler extends Oauth_Callback_Handler {

	const STATE_NONCE_ACTION = 'gravitysmtp_microsoft_oauth';

	const ERROR_TRANSIENT_PREFIX = 'gravitysmtp_microsoft_oauth_error_';

	protected $namespace = 'microsoft';

	protected $proxy_auth_path = 'auth/microsoft';

	public function get_scope() {
		return 'email Mail.Send User.Read profile openid offline_access';
	}

	protected function get_token_url() {
		return 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
	}

	protected function get_token_request_body( $code ) {
		return array(
			'client_id'     => $this->get_client_id(),
			'client_secret' => $this->get_client_secret(),
			'grant_type'    => 'authorization_code',
			'scope'         => $this->get_scope(),
			'code'          => $code,
			'redirect_uri'  => $this->get_return_url( 'settings', false ),
		);
	}

	public function get_refresh_url() {
		return esc_url( 'https://login.microsoftonline.com/common/oauth2/v2.0/token' );
	}

	protected function get_refresh_request_body( $refresh_token ) {
		$body          = parent::get_refresh_request_body( $refresh_token );
		$body['scope'] = $this->get_scope();

		return $body;
	}

	protected function get_token_check_url() {
		return 'https://graph.microsoft.com/v1.0/me';
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

		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$email = ! empty( $body['mail'] ) ? $body['mail'] : '';

		if ( empty( $email ) && ! empty( $body['userPrincipalName'] ) ) {
			$email = $body['userPrincipalName'];
		}

		if ( empty( $email ) ) {
			$email = __( 'Unable to retrieve associated email.', 'gravitysmtp' );
		}

		return array(
			'email' => $email,
		);
	}

}
