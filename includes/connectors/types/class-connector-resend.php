<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Exception;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use WP_Error;

/**
 * Connector for Resend
 *
 * @since 1.0
 */
class Connector_Resend extends Connector_Base {

	const SETTING_API_KEY    = 'api_key';

	protected $name      = 'resend';
	protected $title     = 'Resend';
	protected $disabled  = true;
	protected $logo      = 'Resend';
	protected $full_logo = 'ResendFull';
	protected $url       = 'https://api.resend.com';

	protected $sensitive_fields = array(
		self::SETTING_API_KEY,
	);

	public function get_description() {
		return esc_html__( 'Email for developers that makes it easy to write, format, and send emails. Deliver transactional and marketing emails at scale.', 'gravitysmtp' );
	}

	/**
	 * Sends email via Resend.
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

			$this->logger->log( $email, 'started', __( 'Starting email send for Resend connector.', 'gravitysmtp' ) );

			if ( $this->is_test_mode() ) {
				$this->events->update( array( 'status' => 'sandboxed' ), $email );
				$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );

				return true;
			}

			$response = wp_safe_remote_post( $this->url . '/emails', $params );

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

	/**
	 * Get the request parameters for sending email through connector.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_request_params() {
		$atts       = $this->get_send_atts();
		$api_key    = $this->get_setting( self::SETTING_API_KEY );

		$message = array(
			'to'      => '',
			'from'    => ! empty( $atts['from']['name'] ) ? sprintf( '%s <%s>', $atts['from']['name'], $atts['from']['email'] ) : $atts['from']['email'],
			'subject' => $atts['subject'],
			'headers' => array(),
		);

		$to_value = array();

		foreach ( $atts['to']->as_array() as $recipient ) {
			if ( ! empty( $recipient['name'] ) ) {
				$to_value[] = sprintf( '%s <%s>', $recipient['name'], $recipient['email'] );
			} else {
				$to_value[] = $recipient['email'];
			}
		}

		$message['to'] = $to_value;

		// Setting content
		$is_html = ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false;

		if ( $is_html ) {
			$message['html'] = $atts['message'];
			// Strip tags from plaintext
			$text_body = wp_strip_all_tags( $atts['message'] );

			// Remove leftover double-linebreaks from plaintext.
			$text_body = preg_replace( "/([\r\n]{2,}|[\n]{2,}|[\r]{2,}|[\r\t]{2,}|[\n\t]{2,})/", "\n", $text_body );

			$message['text'] = $text_body;
		} else {
			$message['text'] = $atts['message'];
		}

		// Setting cc
		if ( ! empty( $atts['headers']['cc'] ) ) {
			$cc_headers = array();

			foreach ( $atts['headers']['cc']->as_array() as $cc_value ) {
				$cc_headers[] = ! empty( $cc_value['name'] ) ? sprintf( '%s <%s>', $cc_value['name'], $cc_value['email'] ) : $cc_value['email'];
			}

			$message['cc'] = $cc_headers;
		}

		// Setting Bcc
		if ( ! empty( $atts['headers']['bcc'] ) ) {
			$bcc_headers = array();

			foreach ( $atts['headers']['bcc']->as_array() as $bcc_value ) {
				$bcc_headers[] = ! empty( $bcc_value['name'] ) ? sprintf( '%s <%s>', $bcc_value['name'], $bcc_value['email'] ) : $bcc_value['email'];
			}

			$message['bcc'] = $bcc_headers;
		}

		// Setting reply to
		if ( ! empty( $atts['reply_to'] ) ) {
			if ( isset( $atts['reply_to']['email'] ) ) {
				$reply_to = $atts['reply_to'];
			} else {
				$reply_to = $atts['reply_to'][0];
			}

			$message['reply_to'] = $reply_to['email'];
		}

		// Setting attachments
		if ( ! empty( $atts['attachments'] ) ) {
			$message['attachments'] = $this->get_attachments( $atts['attachments'] );
		}

		return array(
			'body'    => json_encode( $message ),
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
						'filename'       => $fileName,
						'content'        => $content,
						'content_type'   => mime_content_type( $attachment ),
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
	 * @return array returns the header array to be passed to Resend's API
	 */
	protected function get_request_headers( $api_key ) {
		return array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
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
			'title'       => esc_html__( 'Resend Settings', 'gravitysmtp' ),
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
								'label'  => esc_html__( 'API Key', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => __( 'To generate an API key from Resend, log in to your Resend dashboard and navigate to the Credentials section and then generate your API key.', 'gravitysmtp' ),
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
		$api_key    = $this->get_setting( self::SETTING_API_KEY, '' );
		$url        = $this->url . '/domains';

		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'No API Key provided.', 'gravitysmtp' ) );
		}

		$response = wp_remote_get(
			$url,
			array(
				'headers' => $this->get_request_headers( $api_key ),
			)
		);

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		// Some API keys are restricted to only sending; we can't verify them until a test send is attempted.
		if ( strpos( $body, 'restricted_api_key' ) !== false ) {
			return true;
		}

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
		$data['disabled'] = ! Feature_Flag_Manager::is_enabled( 'resend_integration' );

		return $data;
	}
}
