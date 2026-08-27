<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Oauth;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\API\Oauth_Handler as Oauth_Handler_Base;

/**
 * Shared process-redirect-show skeleton for connector OAuth callbacks.
 *
 * Concrete handlers supply the provider-specific token exchange (URL and request
 * body) and any post-exchange work; this class owns the rest of the flow:
 *
 * - Validate the state nonce before touching the single-use authorization code.
 * - Handle denied-consent callbacks (error parameter without a code).
 * - Store failures in a short-lived per-user transient for one-time display.
 * - Redirect to a clean settings URL so the callback never replays on refresh.
 * - Activate the connector server-side once the connection is complete.
 *
 * @since 2.3.3
 */
abstract class Oauth_Callback_Handler extends Oauth_Handler_Base {

	/**
	 * Nonce action for the outgoing OAuth state parameter. Children must override.
	 */
	const STATE_NONCE_ACTION = '';

	/**
	 * Transient key prefix for the per-user callback error. Children must override.
	 */
	const ERROR_TRANSIENT_PREFIX = '';

	const ERROR_INVALID_STATE  = 'invalid_state';
	const ERROR_ACCESS_DENIED  = 'access_denied';
	const ERROR_TOKEN_EXCHANGE = 'token_exchange_failed';

	protected $supports_refresh_token = true;

	protected $response_payload_name = 'code';

	/**
	 * Path under the Gravity API proxy used to start the authorize flow, when the
	 * provider uses the proxy (for example 'auth/gmail'). Leave empty otherwise.
	 *
	 * @since 2.3.3
	 *
	 * @var string
	 */
	protected $proxy_auth_path = '';

	/**
	 * Read-once cache of the stored callback error for the current request.
	 *
	 * @since 2.3.3
	 *
	 * @var string|null
	 */
	private $last_error;

	/**
	 * Per-instance cache of the token validity check for the current request.
	 *
	 * @since 2.3.3
	 *
	 * @var bool|null
	 */
	private $token_validity;

	/**
	 * Get the provider token endpoint URL for the authorization-code exchange.
	 *
	 * @since 2.3.3
	 *
	 * @return string
	 */
	abstract protected function get_token_url();

	/**
	 * Get the request body for the authorization-code exchange.
	 *
	 * @since 2.3.3
	 *
	 * @param string $code The single-use authorization code from the callback.
	 *
	 * @return array
	 */
	abstract protected function get_token_request_body( $code );

	/**
	 * Get the provider URL used to check whether a stored token is still valid.
	 *
	 * @since 2.3.3
	 *
	 * @return string
	 */
	abstract protected function get_token_check_url();

	/**
	 * Process the OAuth callback from the provider, then redirect to a clean settings URL.
	 *
	 * Runs on admin_init so the single-use authorization code is exchanged one time,
	 * before any output. Failures are stored in a short-lived per-user transient for
	 * one-time display via get_last_error().
	 *
	 * @since 2.3.3
	 *
	 * @return void
	 */
	public function handle_response() {
		if ( ! $this->is_response() ) {
			return;
		}

		$error = $this->process_callback();

		if ( ! empty( $error ) ) {
			set_transient( static::ERROR_TRANSIENT_PREFIX . get_current_user_id(), $error, MINUTE_IN_SECONDS );
		}

		$wizard_screen = filter_input( INPUT_GET, 'setup-wizard-page', FILTER_SANITIZE_NUMBER_INT );
		$context       = empty( $wizard_screen ) ? 'settings' : 'wizard';

		$this->redirect( $this->get_return_url( $context, false ) );
	}

	/**
	 * Validate the callback and exchange the authorization code for tokens.
	 *
	 * @since 2.3.3
	 *
	 * @return string One of the ERROR_* constants on failure, empty string on success.
	 */
	private function process_callback() {
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : ''; //phpcs:ignore

		if ( ! wp_verify_nonce( $state, static::STATE_NONCE_ACTION ) ) {
			$this->log_callback_error( 'Rejected callback with an invalid state nonce.' );

			return static::ERROR_INVALID_STATE;
		}

		$provider_error = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';

		if ( ! empty( $provider_error ) ) {
			$this->log_callback_error( sprintf( 'Authorization was not granted: %s.', $provider_error ) );

			return static::ERROR_ACCESS_DENIED;
		}

		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$error = $this->exchange_code( $code );

		if ( '' !== $error ) {
			return $error;
		}

		$this->activate_connector();

		return '';
	}

	/**
	 * Exchange the authorization code for tokens and store them.
	 *
	 * @since 2.3.3
	 *
	 * @param string $code The single-use authorization code from the callback.
	 *
	 * @return string One of the ERROR_* constants on failure, empty string on success.
	 */
	protected function exchange_code( $code ) {
		$request     = wp_remote_post( $this->get_token_url(), array( 'body' => $this->get_token_request_body( $code ) ) );
		$status_code = (int) wp_remote_retrieve_response_code( $request );
		$response    = json_decode( wp_remote_retrieve_body( $request ), true );

		// Some providers (Zoho) return an error payload with a 200 status, so also require the token.
		if ( $status_code !== 200 || empty( $response[ $this->payload_access_token_name ] ) ) {
			$this->log_callback_error( sprintf( 'Token exchange failed with HTTP status %d and error: %s.', $status_code, isset( $response['error'] ) ? $response['error'] : 'unknown' ) );

			return static::ERROR_TOKEN_EXCHANGE;
		}

		$this->store_access_token( $response[ $this->payload_access_token_name ] );

		if ( isset( $response[ $this->payload_refresh_token_name ] ) ) {
			$this->store_refresh_token( $response[ $this->payload_refresh_token_name ] );
		}

		return $this->after_token_exchange( $response );
	}

	/**
	 * Complete any provider-specific work after a successful token exchange.
	 *
	 * Children that need extra data (for example an account lookup) override this
	 * and return an ERROR_* code on failure, clearing the stored tokens so a
	 * half-connected state never displays as connected.
	 *
	 * @since 2.3.3
	 *
	 * @param array $response The decoded token endpoint response.
	 *
	 * @return string An ERROR_* code on failure, empty string on success.
	 */
	protected function after_token_exchange( $response ) {
		return '';
	}

	/**
	 * Mark the connector enabled now that the connection is complete.
	 *
	 * The credential save that starts the OAuth flow does not activate the
	 * connector; activation waits for the token so a failed or abandoned attempt
	 * never surfaces as an active integration. Mirrors the writes performed by
	 * Save_Connector_Settings_Endpoint::save_connector_status().
	 *
	 * @since 2.3.3
	 *
	 * @return void
	 */
	protected function activate_connector() {
		$plugin_data_store = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS );

		$this->data->save( Connector_Base::SETTING_CONFIGURED, true, $this->namespace );
		$this->data->save( Connector_Base::SETTING_ENABLED, true, $this->namespace );

		$enabled_values = $plugin_data_store->get( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR );

		if ( ! is_array( $enabled_values ) ) {
			$enabled_values = array();
		}

		$enabled_values[ $this->namespace ] = true;
		$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, $enabled_values );

		// Become the primary connector only when no other connector holds that role.
		$primary_values = $plugin_data_store->get( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR );

		if ( ! is_array( $primary_values ) ) {
			$primary_values = array();
		}

		foreach ( $primary_values as $value ) {
			if ( Booliesh::get( $value ) ) {
				$this->refresh_configured_state();

				return;
			}
		}

		$this->data->save( Connector_Base::SETTING_IS_PRIMARY, true, $this->namespace );
		$primary_values[ $this->namespace ] = true;
		$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, $primary_values );

		$this->refresh_configured_state();
	}

	/**
	 * Clear the cached configured flag so the UI reflects the new connection.
	 *
	 * @since 2.3.3
	 *
	 * @return void
	 */
	protected function refresh_configured_state() {
		delete_transient( sprintf( 'gsmtp_connector_configured_%s', $this->namespace ) );
	}

	/**
	 * Get the stored callback error for the current user, then delete it.
	 *
	 * @since 2.3.3
	 *
	 * @return string One of the ERROR_* constants, or empty string when no error is stored.
	 */
	public function get_last_error() {
		if ( ! is_null( $this->last_error ) ) {
			return $this->last_error;
		}

		$key              = static::ERROR_TRANSIENT_PREFIX . get_current_user_id();
		$this->last_error = (string) get_transient( $key );

		if ( '' !== $this->last_error ) {
			delete_transient( $key );
		}

		return $this->last_error;
	}

	/**
	 * Redirect to the given URL and end the request.
	 *
	 * @since 2.3.3
	 *
	 * @param string $url The redirect destination.
	 *
	 * @return void
	 */
	protected function redirect( $url ) {
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Write a callback failure to the debug log. Never log secrets.
	 *
	 * @since 2.3.3
	 *
	 * @param string $message The failure detail.
	 *
	 * @return void
	 */
	protected function log_callback_error( $message ) {
		Debug_Logger::log_message( sprintf( '%s OAuth: %s', ucfirst( $this->namespace ), $message ), 'error' );
	}

	protected function is_response() {
		$page          = filter_input( INPUT_GET, 'page' );
		$tab           = filter_input( INPUT_GET, 'tab' );
		$integration   = filter_input( INPUT_GET, 'integration' );
		$wizard_screen = filter_input( INPUT_GET, 'setup-wizard-page', FILTER_SANITIZE_NUMBER_INT );
		$payload       = filter_input( INPUT_GET, $this->response_payload_name );
		$error         = filter_input( INPUT_GET, 'error' );

		// Valid screens are a specific integration tab, or the setup wizard page.
		$is_valid_screen = $page === 'gravitysmtp-settings' &&
		                   (
			                   ( $tab === 'integrations' && $integration === $this->namespace ) ||
			                   ( ! empty( $wizard_screen ) )
		                   );

		// Denied-consent callbacks carry an error parameter and no code.
		return ( ! empty( $payload ) || ! empty( $error ) ) && $is_valid_screen;
	}

	public function get_return_url( $context = 'settings', $encode = true ) {
		$base = admin_url( 'admin.php' );

		if ( $context === 'copy' ) {
			return $base;
		}

		$args = array(
			'page' => 'gravitysmtp-settings',
		);

		if ( $context === 'settings' ) {
			$args['integration'] = $this->namespace;
			$args['tab']         = 'integrations';
		}

		if ( $context === 'wizard' ) {
			$args['tab']               = 'integrations';
			$args['setup-wizard-page'] = 4;
		}

		$value = add_query_arg( $args, $base );

		if ( ! $encode ) {
			return $value;
		}

		return urlencode( $value );
	}

	public function get_oauth_url( $context = 'settings' ) {
		if ( empty( $this->proxy_auth_path ) ) {
			return '';
		}

		$state = array(
			'url'   => admin_url( 'admin.php' ),
			'page'  => 'gravitysmtp-settings',
			'nonce' => wp_create_nonce( 'gravitysmtp' ),
		);

		if ( $context === 'settings' ) {
			$state['tab']         = 'integrations';
			$state['integration'] = $this->namespace;
		}

		$auth_url = add_query_arg(
			array(
				'redirect_to' => $this->get_return_url( $context ),
				'state'       => base64_encode( json_encode( $state ) ),
				'license'     => $this->data->get( Save_Plugin_Settings_Endpoint::PARAM_LICENSE_KEY ),
			),
			trailingslashit( GRAVITY_API_URL ) . $this->proxy_auth_path
		);

		return esc_url( $auth_url );
	}

	protected function refresh_expired_token() {
		$refresh_token = $this->get_refresh_token();

		if ( empty( $refresh_token ) ) {
			return new \WP_Error( __( 'Token is invalid or expired.', 'gravitysmtp' ) );
		}

		$response = $this->request_refreshed_token( $refresh_token );
		$code     = wp_remote_retrieve_response_code( $response );

		if ( (int) $code !== 200 ) {
			return new \WP_Error( __( 'Token is invalid or expired.', 'gravitysmtp' ) );
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response_body[ $this->payload_access_token_name ] ) ) {
			return new \WP_Error( __( 'Token is invalid or expired.', 'gravitysmtp' ) );
		}

		$new_token = $response_body[ $this->payload_access_token_name ];
		$this->store_access_token( $new_token );

		if ( ! empty( $response_body[ $this->payload_refresh_token_name ] ) ) {
			$this->store_refresh_token( $response_body[ $this->payload_refresh_token_name ] );
		}

		return $new_token;
	}

	/**
	 * Send the refresh-token request to the provider.
	 *
	 * @since 2.3.3
	 *
	 * @param string $refresh_token The stored refresh token.
	 *
	 * @return array|\WP_Error The wp_remote_post() response.
	 */
	protected function request_refreshed_token( $refresh_token ) {
		return wp_remote_post( $this->get_refresh_url(), array( 'body' => $this->get_refresh_request_body( $refresh_token ) ) );
	}

	/**
	 * Get the request body for the refresh-token request.
	 *
	 * @since 2.3.3
	 *
	 * @param string $refresh_token The stored refresh token.
	 *
	 * @return array
	 */
	protected function get_refresh_request_body( $refresh_token ) {
		return array(
			'refresh_token' => $refresh_token,
			'client_id'     => $this->get_client_id(),
			'client_secret' => $this->get_client_secret(),
			'grant_type'    => 'refresh_token',
		);
	}

	protected function is_valid_token( $token ) {
		if ( ! is_null( $this->token_validity ) ) {
			return $this->token_validity;
		}

		if ( empty( $token ) ) {
			return false;
		}

		$response = wp_remote_get( $this->get_token_check_url(), array( 'headers' => $this->get_auth_headers( $token ) ) );
		$code     = wp_remote_retrieve_response_code( $response );

		$this->token_validity = (int) $code === 200;

		return $this->token_validity;
	}

	/**
	 * Get the authorization headers for provider API requests.
	 *
	 * @since 2.3.3
	 *
	 * @param string $token The access token.
	 *
	 * @return array
	 */
	protected function get_auth_headers( $token ) {
		return array(
			'Authorization' => 'Bearer ' . $token,
		);
	}

	/**
	 * Get the stored OAuth client ID. All OAuth connectors store it under this key.
	 *
	 * @since 2.3.3
	 *
	 * @return string
	 */
	protected function get_client_id() {
		return $this->data->get( 'client_id', $this->namespace );
	}

	/**
	 * Get the stored OAuth client secret. All OAuth connectors store it under this key.
	 *
	 * @since 2.3.3
	 *
	 * @return string
	 */
	protected function get_client_secret() {
		return $this->data->get( 'client_secret', $this->namespace );
	}

}
