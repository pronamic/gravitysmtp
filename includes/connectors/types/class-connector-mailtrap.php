<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Exception;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use WP_Error;

/**
 * Connector for Mailtrap
 *
 * @since 1.9.5
 */
class Connector_Mailtrap extends Connector_Base {

	const SETTING_API_KEY         = 'api_key';
	const SETTING_SANDBOX_ENABLED = 'sandbox_enabled';
	const SETTING_INBOX_ID        = 'inbox_id';

	protected $name      = 'mailtrap';
	protected $title     = 'Mailtrap';
	protected $disabled  = true;
	protected $logo      = 'Mailtrap';
	protected $full_logo = 'MailtrapFull';

	protected $sensitive_fields = array(
		self::SETTING_API_KEY,
	);

	/**
	 * Get the description for this connector.
	 *
	 * @since 1.9.5
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'Mailtrap is an email delivery platform for businesses and individuals to test, send, and control email infrastructure in one place.', 'gravitysmtp' );
	}

	/**
	 * Sends email via Mailtrap.
	 *
	 * @since 1.9.5
	 *
	 * @return bool|int Returns true on success, or the email ID on failure.
	 */
	public function send() {
		try {
			$atts   = $this->get_send_atts();
			$source = $this->get_att( 'source' );
			$params = $this->get_request_params();
			$email  = $this->email;

			$this->set_email_log_data( $atts['subject'], $atts['message'], $atts['to'], $atts['from']['from'], $atts['headers'], $atts['attachments'], $source, $params );

			$this->logger->log( $email, 'started', __( 'Starting email send for Mailtrap connector.', 'gravitysmtp' ) );

			if ( $this->is_test_mode() ) {
				$this->events->update( array( 'status' => 'sandboxed' ), $email );
				$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );

				return true;
			}

			$url      = $this->get_send_url();
			$response = wp_safe_remote_post( $url, $params );

			$response_code = (int) wp_remote_retrieve_response_code( $response );
			$is_success    = in_array( $response_code, array( 200, 201, 202 ) );

			if ( ! $is_success ) {
				$body          = wp_remote_retrieve_body( $response );
				$error_message = $this->get_api_error_message( $response_code, $body );
				$this->log_failure( $email, $error_message );

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

	/**
	 * Get the URL to send email to, switching between transactional and sandbox endpoints.
	 *
	 * @since 1.9.5
	 *
	 * @return string
	 */
	private function get_send_url() {
		$sandbox_enabled = (bool) $this->get_setting( self::SETTING_SANDBOX_ENABLED, false );

		if ( $sandbox_enabled ) {
			$inbox_id = absint( $this->get_setting( self::SETTING_INBOX_ID, '' ) );

			// Sandbox endpoint requires the inbox ID in the URL path.
			return sprintf( 'https://sandbox.api.mailtrap.io/api/send/%d', $inbox_id );
		}

		return 'https://send.api.mailtrap.io/api/send';
	}

	/**
	 * Get the request parameters for sending email through connector.
	 *
	 * @since 1.9.5
	 *
	 * @return array
	 */
	public function get_request_params() {
		$atts    = $this->get_send_atts();
		$api_key = $this->get_setting( self::SETTING_API_KEY );

		$body = array(
			'from'    => array(
				'email' => $atts['from']['email'],
				'name'  => isset( $atts['from']['name'] ) ? $atts['from']['name'] : '',
			),
			'subject' => $atts['subject'],
		);

		// Build to recipients as array of objects.
		$to_value = array();

		foreach ( $atts['to']->as_array() as $recipient ) {
			$to_entry = array( 'email' => $recipient['email'] );

			if ( ! empty( $recipient['name'] ) ) {
				$to_entry['name'] = $recipient['name'];
			}

			$to_value[] = $to_entry;
		}

		$body['to'] = $to_value;

		// Set content — Mailtrap accepts both html and text fields.
		$is_html = ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false;

		if ( $is_html ) {
			$body['html'] = $atts['message'];

			// Strip tags for plain text fallback.
			$text_body = wp_strip_all_tags( $atts['message'] );

			// Remove leftover double-linebreaks from plaintext.
			$text_body    = preg_replace( "/([\r\n]{2,}|[\n]{2,}|[\r]{2,}|[\r\t]{2,}|[\n\t]{2,})/", "\n", $text_body );
			$body['text'] = $text_body;
		} else {
			$body['text'] = $atts['message'];
		}

		// CC recipients.
		if ( ! empty( $atts['headers']['cc'] ) ) {
			$cc_value = array();

			foreach ( $atts['headers']['cc']->as_array() as $cc_recipient ) {
				$cc_entry = array( 'email' => $cc_recipient['email'] );

				if ( ! empty( $cc_recipient['name'] ) ) {
					$cc_entry['name'] = $cc_recipient['name'];
				}

				$cc_value[] = $cc_entry;
			}

			$body['cc'] = $cc_value;
		}

		// BCC recipients.
		if ( ! empty( $atts['headers']['bcc'] ) ) {
			$bcc_value = array();

			foreach ( $atts['headers']['bcc']->as_array() as $bcc_recipient ) {
				$bcc_entry = array( 'email' => $bcc_recipient['email'] );

				if ( ! empty( $bcc_recipient['name'] ) ) {
					$bcc_entry['name'] = $bcc_recipient['name'];
				}

				$bcc_value[] = $bcc_entry;
			}

			$body['bcc'] = $bcc_value;
		}

		// Reply-to.
		if ( ! empty( $atts['reply_to'] ) ) {
			if ( isset( $atts['reply_to']['email'] ) ) {
				$reply_to = $atts['reply_to'];
			} else {
				$reply_to = $atts['reply_to'][0];
			}

			$reply_to_entry = array( 'email' => $reply_to['email'] );

			if ( ! empty( $reply_to['name'] ) ) {
				$reply_to_entry['name'] = $reply_to['name'];
			}

			$body['reply_to'] = $reply_to_entry;
		}

		// Attachments.
		if ( ! empty( $atts['attachments'] ) ) {
			$body['attachments'] = $this->get_attachments( $atts['attachments'] );
		}

		return array(
			'body'    => json_encode( $body ),
			'headers' => $this->get_request_headers( $api_key ),
		);
	}

	/**
	 * Get the attributes for sending email.
	 *
	 * @since 1.9.5
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
	 * Gets a list of attachments formatted for the Mailtrap API.
	 *
	 * @since 1.9.5
	 *
	 * @param array $attachments The list of attachments.
	 *
	 * @return array Returns an array of attachments with filename, content, type, and disposition.
	 */
	protected function get_attachments( $attachments ) {
		$data = array();

		foreach ( $attachments as $custom_name => $attachment ) {
			try {
				if ( is_file( $attachment ) && is_readable( $attachment ) ) {
					$file_name = is_numeric( $custom_name ) ? basename( $attachment ) : $custom_name;
					$content   = base64_encode( file_get_contents( $attachment ) );
					$mime_type = mime_content_type( $attachment );

					$data[] = array(
						'filename'    => $file_name,
						'content'     => $content,
						'type'        => $mime_type,
						'disposition' => 'attachment',
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
	 * @since 1.9.5
	 *
	 * @param string $api_key The Mailtrap API token.
	 *
	 * @return array Returns the header array to be passed to Mailtrap's API.
	 */
	protected function get_request_headers( $api_key ) {
		return array(
			'Content-Type' => 'application/json',
			'Api-Token'    => $api_key,
		);
	}

	/**
	 * Logs an email send failure.
	 *
	 * @since 1.9.5
	 *
	 * @param string $email         The email log ID.
	 * @param string $error_message The error message.
	 */
	private function log_failure( $email, $error_message ) {
		$this->events->update( array( 'status' => 'failed' ), $email );
		$this->logger->log( $email, 'failed', $error_message );
	}

	/**
	 * Parses an API error response into a human-readable message.
	 *
	 * @since 1.9.5
	 *
	 * @param int    $response_code The HTTP response code.
	 * @param string $body          The raw response body.
	 *
	 * @return string The error message.
	 */
	private function get_api_error_message( $response_code, $body ) {
		// Rate limit exceeded — surface a clear message so admins know to wait.
		if ( $response_code === 429 ) {
			return __( 'Mailtrap rate limit exceeded. Please wait before sending again.', 'gravitysmtp' );
		}

		$decoded = json_decode( $body, true );

		if ( ! empty( $decoded['errors'] ) && is_array( $decoded['errors'] ) ) {
			return implode( ' ', $decoded['errors'] );
		}

		return $body;
	}

	/**
	 * Connector data.
	 *
	 * @since 1.9.5
	 *
	 * @return array
	 */
	public function connector_data() {
		return array(
			self::SETTING_API_KEY              => $this->get_setting( self::SETTING_API_KEY, '' ),
			self::SETTING_SANDBOX_ENABLED      => $this->get_setting( self::SETTING_SANDBOX_ENABLED, false ),
			self::SETTING_INBOX_ID             => $this->get_setting( self::SETTING_INBOX_ID, '' ),
			self::SETTING_FROM_EMAIL           => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
			self::SETTING_FORCE_FROM_EMAIL     => $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
			self::SETTING_FROM_NAME            => $this->get_setting( self::SETTING_FROM_NAME, '' ),
			self::SETTING_FORCE_FROM_NAME      => $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
			self::SETTING_REPLY_TO_EMAIL       => $this->get_setting( self::SETTING_REPLY_TO_EMAIL, '' ),
			self::SETTING_FORCE_REPLY_TO_EMAIL => $this->get_setting( self::SETTING_FORCE_REPLY_TO_EMAIL, false ),
		);
	}

	/**
	 * Settings fields.
	 *
	 * @since 1.9.5
	 *
	 * @return array
	 */
	public function settings_fields() {
		$sandbox_enabled = (bool) $this->get_setting( self::SETTING_SANDBOX_ENABLED, false );

		$fields = array(
			'title'       => esc_html__( 'Mailtrap Settings', 'gravitysmtp' ),
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
						'component' => 'Input',
						'props'     => array(
							'labelAttributes'    => array(
								'label'  => esc_html__( 'API Token', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'To obtain an API token from Mailtrap, log in to your %1$sMailtrap dashboard%2$s and navigate to API Tokens under your account settings.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://mailtrap.io/api-tokens" target="_blank" rel="noopener noreferrer">', '</a>' ),
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
						'component' => 'Toggle',
						'props'     => array(
							'helpTextAttributes' => array(
								'content' => esc_html__( 'When enabled, emails are sent to your Mailtrap sandbox inbox instead of real recipients. Requires a Sandbox Inbox ID.', 'gravitysmtp' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'initialChecked'     => $sandbox_enabled,
							'labelAttributes'    => array(
								'label'  => esc_html__( 'Enable Sandbox Mode', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'labelPosition'      => 'left',
							'name'               => self::SETTING_SANDBOX_ENABLED,
							'size'               => 'size-m',
							'spacing'            => 6,
							'width'              => 'full',
						),
					),
					array(
						'component' => 'Input',
						'props'     => array(
							'labelAttributes'    => array(
								'label'  => esc_html__( 'Sandbox Inbox ID', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'Enter the numeric Inbox ID from your %1$sMailtrap sandbox%2$s. Required when Sandbox Mode is enabled.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://mailtrap.io/sandboxes" target="_blank" rel="noopener noreferrer">', '</a>' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'name'               => self::SETTING_INBOX_ID,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_INBOX_ID, '' ),
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
				),
				$this->get_from_settings_fields(),
				$this->get_reply_to_settings_fields(),
			),
		);

		return $fields;
	}

	/**
	 * Determine if the API credentials are configured correctly.
	 *
	 * @since 1.9.5
	 *
	 * @return bool|WP_Error Returns true if configured, or a WP_Error object if not.
	 */
	public function is_configured() {
		$sandbox_enabled = (bool) $this->get_setting( self::SETTING_SANDBOX_ENABLED, false );

		// When sandbox mode is on, inbox_id is required for sending to work.
		if ( $sandbox_enabled ) {
			$inbox_id = $this->get_setting( self::SETTING_INBOX_ID, '' );

			if ( empty( $inbox_id ) ) {
				$error            = new WP_Error( 'missing_inbox_id', __( 'Sandbox Inbox ID is required when Sandbox Mode is enabled.', 'gravitysmtp' ) );
				self::$configured = $error;

				return $error;
			}
		}

		$valid_api = $this->verify_api_key();

		if ( is_wp_error( $valid_api ) ) {
			self::$configured = $valid_api;

			return $valid_api;
		}

		self::$configured = true;

		return true;
	}

	/**
	 * Verify the API token with the Mailtrap accounts endpoint.
	 *
	 * @since 1.9.5
	 *
	 * @return true|WP_Error
	 */
	private function verify_api_key() {
		$api_key = $this->get_setting( self::SETTING_API_KEY, '' );

		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'No API Token provided.', 'gravitysmtp' ) );
		}

		$response = wp_remote_get(
			'https://mailtrap.io/api/accounts',
			array(
				'headers' => $this->get_request_headers( $api_key ),
			)
		);

		if ( wp_remote_retrieve_response_code( $response ) != '200' ) {
			return new WP_Error( 'invalid_api_key', __( 'Invalid API Token provided.', 'gravitysmtp' ) );
		}

		return true;
	}

	/**
	 * Get the unique data for this connector, merged with the default/common data for all
	 * connectors in the system.
	 *
	 * @since 1.9.5
	 *
	 * @return array
	 */
	protected function get_merged_data() {
		$data             = parent::get_merged_data();
		$data['disabled'] = ! Feature_Flag_Manager::is_enabled( 'mailtrap_integration' );

		return $data;
	}

}
