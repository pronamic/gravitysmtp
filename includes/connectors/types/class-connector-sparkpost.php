<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Exception;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use WP_Error;

/**
 * Connector for SparkPost
 *
 * @since 1.0
 */
class Connector_Sparkpost extends Connector_Base {

	const SETTING_API_KEY          = 'api_key';
	const SETTING_ACCOUNT_LOCATION = 'account_location';
	const SETTING_USE_RETURN_PATH  = 'use_return_path';

	protected $name      = 'sparkpost';
	protected $title     = 'SparkPost';
	protected $disabled  = true;
	protected $logo      = 'SparkPost';
	protected $full_logo = 'SparkPostFull';
	protected $url       = 'https://api.sparkpost.com/api/v1';
	protected $url_eu    = 'https://api.eu.sparkpost.com/api/v1';

	protected $sensitive_fields = array(
		self::SETTING_API_KEY,
	);

	public function get_description() {
		return esc_html__( 'SparkPost provides email delivery services and transactional email for developers at companies of all sizes.', 'gravitysmtp' );
	}

	/**
	 * Sends email via SparkPost.
	 *
	 * @since 1.0
	 *
	 * @return int returns the email ID
	 */
	public function send() {
		try {
			$atts   = $this->get_send_atts();
			$source = $this->get_att( 'source' );
			$params = $this->get_request_params();
			$email  = $this->email;

			$this->set_email_log_data( $atts['subject'], $atts['message'], $atts['to'], $atts['from']['from'], $atts['headers'], $atts['attachments'], $source, $params );

			$this->logger->log( $email, 'started', __( 'Starting email send for SparkPost connector.', 'gravitysmtp' ) );

			if ( $this->is_test_mode() ) {
				$this->events->update( array( 'status' => 'sandboxed' ), $email );
				$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );

				return true;
			}

			$response = wp_safe_remote_post( $this->get_base_url() . '/transmissions/', $params );

			$is_success = in_array( (int) wp_remote_retrieve_response_code( $response ), array( 200, 201, 202 ) );

			if ( ! $is_success ) {
				$this->log_failure( $email, wp_remote_retrieve_body( $response ) );

				return $email;
			}

			$this->events->update( array( 'status' => 'sent' ), $email );
			$this->logger->log( $email, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );

			return true;
		} catch ( Exception $e ) {
			$this->log_failure( $email, $e->getMessage() );

			return $email;
		}
	}

	protected function get_base_url() {
		$location = $this->get_setting( self::SETTING_ACCOUNT_LOCATION, 'us' );

		return $location !== 'us' ? $this->url_eu : $this->url;
	}

	/**
	 * Get the request parameters for sending email through connector.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_request_params() {
		$atts                  = $this->get_send_atts();
		$api_key               = $this->get_setting( self::SETTING_API_KEY );
		$send_as_transactional = apply_filters( 'gravitysmtp_sparkpost_send_as_transactional', true );

		$body = array(
			'recipients' => array(),
			'content'    => array(
				'from'    => array(
					'name'  => $atts['from']['name'],
					'email' => $atts['from']['email'],
				),
				'subject' => $atts['subject'],
				'headers' => array(),
			),
			'options'    => array(
				'transactional' => $send_as_transactional,
			),
		);

		foreach ( $atts['to']->as_array() as $recipient ) {
			$body['recipients'][] = array( 'address' => $recipient );
		}

		// Setting content
		$is_html = ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false;

		if ( $is_html ) {
			$body['content']['html'] = $atts['message'];
		} else {
			$body['content']['text'] = $atts['message'];
		}

		$main_recipient = $atts['to']->first()->as_array();

		// Setting cc
		if ( ! empty( $atts['headers']['cc'] ) ) {
			$cc_headers = array();

			foreach ( $atts['headers']['cc']->as_array() as $cc_value ) {
				$values = array(
					'email'     => $cc_value['email'],
					'name'      => ! empty( $cc_value['name'] ) ? $cc_value['name'] : null,
					'header_to' => $main_recipient['email'],
				);

				$body['recipients'][] = array( 'address' => array_filter( $values ) );
				$cc_headers[]         = $cc_value['email'];
			}

			$body['content']['headers']['CC'] = implode( ', ', $cc_headers );
		}

		// Setting bcc
		if ( ! empty( $atts['headers']['bcc'] ) ) {
			foreach ( $atts['headers']['bcc']->as_array() as $bcc_value ) {
				$values = array(
					'email'     => $bcc_value['email'],
					'name'      => ! empty( $bcc_value['name'] ) ? $bcc_value['name'] : null,
					'header_to' => $main_recipient['email'],
				);

				$body['recipients'][] = array( 'address' => array_filter( $values ) );
			}
		}

		// Setting reply to
		if ( ! empty( $atts['reply_to'] ) ) {
			if ( isset( $atts['reply_to']['email'] ) ) {
				$reply_to = $atts['reply_to'];
			} else {
				$reply_to = $atts['reply_to'][0];
			}

			$body['content']['reply_to'] = $reply_to['email'];
		}

		// Setting attachments
		if ( ! empty( $atts['attachments'] ) ) {
			$body['content']['attachments'] = $this->get_attachments( $atts['attachments'] );
		}

		if ( empty( $body['content']['headers'] ) ) {
			unset( $body['content']['headers'] );
		}

		return array(
			'body'    => json_encode( $body ),
			'headers' => $this->get_request_headers( $api_key ),
		);
	}

	/**
	 * Get the attributes for sending email.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_send_atts() {
		$headers = $this->get_parsed_headers( $this->get_att( 'headers', array() ) );

		if ( ! empty( $headers['content-type'] ) ) {
			$headers['content-type'] = $this->get_att( 'content_type', $headers['content-type'] );
		}

		return array(
			'to'          => $this->get_att( 'to', '' ),
			'subject'     => $this->get_att( 'subject', '' ),
			'message'     => $this->get_att( 'message', '' ),
			'headers'     => $headers,
			'attachments' => $this->get_att( 'attachments', array() ),
			'from'        => $this->get_from( true ),
			'reply_to'    => $this->get_reply_to( true ),
		);
	}

	/**
	 * Gets a list of attachments, and returns them in a format that can be used by the API.
	 *
	 * @param array $attachments the list of attachments
	 *
	 * @since 1.0
	 *
	 * @return array Returns an array of attachments. Each item with a 'content' and 'name' property.
	 */
	protected function get_attachments( $attachments ) {
		$data = array();

		foreach ( $attachments as $custom_name => $attachment ) {
			try {
				if ( is_file( $attachment ) && is_readable( $attachment ) ) {
					$fileName = is_numeric( $custom_name ) ? basename( $attachment ) : $custom_name;
					$content  = base64_encode( file_get_contents( $attachment ) );

					$data[] = array(
						'name' => $fileName,
						'data' => $content,
						'type' => mime_content_type( $attachment ),
					);
				}
			} catch ( Exception $e ) {
				continue;
			}
		}

		return $data;
	}

	/**
	 * Gets the headers to be used in the API request.
	 *
	 * @since 1.0
	 *
	 * @return array returns the header array to be passed to SparkPost's API
	 */
	protected function get_request_headers( $api_key ) {
		return array(
			'content-type'  => 'application/json',
			'accept'        => 'application/json',
			'Authorization' => $api_key,
		);
	}

	/**
	 * Logs an email send failure.
	 *
	 * @since 1.0
	 *
	 * @param string $email         the email that failed
	 * @param string $error_message the error message
	 */
	private function log_failure( $email, $error_message ) {
		$this->events->update( array( 'status' => 'failed' ), $email );
		$this->logger->log( $email, 'failed', $error_message );
	}

	/**
	 * Connector data.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function connector_data() {
		return array(
			self::SETTING_API_KEY          => $this->get_setting( self::SETTING_API_KEY, '' ),
			self::SETTING_FROM_EMAIL       => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
			self::SETTING_FORCE_FROM_EMAIL => $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
			self::SETTING_FROM_NAME        => $this->get_setting( self::SETTING_FROM_NAME, '' ),
			self::SETTING_FORCE_FROM_NAME  => $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
		);
	}

	/**
	 * Settings fields.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function settings_fields() {
		return array(
			'title'       => esc_html__( 'SparkPost Settings', 'gravitysmtp' ),
			'description' => '',
			'fields'      => array_merge(
				array(
					array(
						'component' => 'Heading',
						'props'     => array(
							'content' => esc_html__( 'Configuration', 'gravitysmtp' ),
							'size'    => 'text-sm',
							'spacing' => array( 4, 0, 4, 0 ),
							'tagName' => 'h3',
							'type'    => 'boxed',
							'weight'  => 'medium',
						),
					),
					array(
						'component' => 'Select',
						'props'     => array(
							'labelAttributes' => array(
								'label'  => esc_html__( 'Account Location', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'name'            => self::SETTING_ACCOUNT_LOCATION,
							'size'            => 'size-l',
							'spacing'         => 6,
							'initialValue'    => $this->get_setting( self::SETTING_ACCOUNT_LOCATION, 'us' ),
							'options'         => array(
								array(
									'label' => __( 'United States', 'gravitysmtp' ),
									'value' => 'us',
								),
								array(
									'label' => __( 'Europe', 'gravitysmtp' ),
									'value' => 'eu',
								),
							),
						),
					),
					array(
						'component' => 'Input',
						'props'     => array(
							'labelAttributes'    => array(
								'label'  => esc_html__( 'API Key', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'To generate an API key from SparkPost, log in to your SparkPost dashboard, navigate to the API Keys section, and %1$sgenerate your API key%2$s.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://app.sparkpost.com/account/api-keys" target="_blank" rel="noopener noreferrer">', '</a>' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'name'               => self::SETTING_API_KEY,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_API_KEY, '' ),
						),
					),
					array(
						'component' => 'Heading',
						'props'     => array(
							'content' => esc_html__( 'General Settings', 'gravitysmtp' ),
							'size'    => 'text-sm',
							'spacing' => 4,
							'tagName' => 'h3',
							'type'    => 'boxed',
							'weight'  => 'medium',
						),
					),
					array(
						'component' => 'Toggle',
						'props'     => array(
							'helpTextAttributes' => array(
								'content' => esc_html__( 'If Return Path is enabled this adds the return path to the email header which indicates where non-deliverable notifications should be sent. Bounce messages may be lost if not enabled.', 'gravitysmtp' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
								'spacing' => array( 2, 0, 0, 0 ),
							),
							'helpTextWidth'      => 'full',
							'initialChecked'     => (bool) $this->get_setting( self::SETTING_USE_RETURN_PATH, false ),
							'labelAttributes'    => array(
								'label' => esc_html__( 'Return Path', 'gravitysmtp' ),
							),
							'labelPosition'      => 'left',
							'name'               => self::SETTING_USE_RETURN_PATH,
							'size'               => 'size-m',
							'spacing'            => 5,
							'width'              => 'full',
						),
					),
				),
				$this->get_from_settings_fields(),
			),
		);
	}

	/**
	 * Determine if the API credentials are configured correctly.
	 *
	 * @since 1.0
	 *
	 * @return bool|WP_Error returns true if configured, or a WP_Error object if not
	 */
	public function is_configured() {
		$valid_api = $this->verify_api_key();

		if ( is_wp_error( $valid_api ) ) {
			self::$configured = $valid_api;

			return $valid_api;
		}

		self::$configured = true;

		return true;
	}

	/**
	 * Verify the API key with the API.
	 *
	 * @since 1.0
	 *
	 * @return true|WP_Error
	 */
	private function verify_api_key() {
		$api_key = $this->get_setting( self::SETTING_API_KEY );
		$url     = $this->get_base_url() . '/sending-domains?ownership_verified=true';

		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'No API Key provided.', 'gravitysmtp' ) );
		}

		$response = wp_remote_get(
			$url,
			array(
				'headers' => $this->get_request_headers( $api_key ),
			)
		);

		if ( wp_remote_retrieve_response_code( $response ) != '200' ) {
			return new WP_Error( 'invalid_api_key', __( 'Invalid API Key provided.', 'gravitysmtp' ) );
		}

		return true;
	}

	/**
	 * Get the unique data for this connector, merged with the default/common data for all
	 * connectors in the system.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_merged_data() {
		$data             = parent::get_merged_data();
		$data['disabled'] = ! Feature_Flag_Manager::is_enabled( 'sparkpost_integration' );

		return $data;
	}
}
