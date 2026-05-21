<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Exception;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use WP_Error;

/**
 * Connector for SMTP.com
 *
 * @since 1.0
 */
class Connector_SMTPCom extends Connector_Base {

	const SETTING_API_KEY = 'api_key';
	const SETTING_CHANNEL = 'channel';

	protected $name      = 'smtpcom';
	protected $title     = 'SMTP.com';
	protected $disabled  = true;
	protected $logo      = 'SMTP';
	protected $full_logo = 'SMTPFull';
	protected $url       = 'https://api.smtp.com/v4';

	protected $sensitive_fields = array(
		self::SETTING_API_KEY,
	);

	public function get_description() {
		return esc_html__( 'Premium email delivery service with a powerful REST API for sending transactional and marketing emails at scale.', 'gravitysmtp' );
	}

	/**
	 * Sends email via SMTP.com.
	 *
	 * @since 1.0
	 *
	 * @return mixed
	 */
	public function send() {
		try {
			$atts   = $this->get_send_atts();
			$source = $this->get_att( 'source' );
			$params = $this->get_request_params();
			$email  = $this->email;

			$this->set_email_log_data( $atts['subject'], $atts['message'], $atts['to'], $atts['from']['from'], $atts['headers'], $atts['attachments'], $source, $params );

			$this->logger->log( $email, 'started', __( 'Starting email send for SMTP.com connector.', 'gravitysmtp' ) );

			if ( $this->is_test_mode() ) {
				$this->events->update( array( 'status' => 'sandboxed' ), $email );
				$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );

				return true;
			}

			$response = wp_safe_remote_post( $this->url . '/messages', $params );

			if ( is_wp_error( $response ) ) {
				$this->log_failure( $email, $response->get_error_message() );

				return $email;
			}

			$response_code = (int) wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $response_body, true );

			// Handle HTTP-level errors without valid JSON
			if ( empty( $response_data ) || ! is_array( $response_data ) ) {
				if ( $response_code !== 200 ) {
					$this->log_failure( $email, __( 'Unexpected response from SMTP.com.', 'gravitysmtp' ) );

					return $email;
				}
			}

			// Handle HTTP status errors with specific messages
			if ( $response_code === 401 ) {
				$this->log_failure( $email, __( 'Invalid API key.', 'gravitysmtp' ) );

				return $email;
			}

			if ( $response_code === 403 ) {
				$this->log_failure( $email, __( 'Forbidden — check channel permissions.', 'gravitysmtp' ) );

				return $email;
			}

			if ( $response_code === 429 ) {
				$this->log_failure( $email, __( 'Rate limit exceeded.', 'gravitysmtp' ) );

				return $email;
			}

			if ( $response_code >= 500 ) {
				$this->log_failure( $email, __( 'SMTP.com server error.', 'gravitysmtp' ) );

				return $email;
			}

			// Parse JSend response format
			if ( isset( $response_data['status'] ) ) {
				if ( $response_data['status'] === 'success' ) {
					$this->events->update( array( 'status' => 'sent' ), $email );
					$this->logger->log( $email, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );

					return true;
				}

				if ( $response_data['status'] === 'error' ) {
					$error_message = isset( $response_data['message'] ) ? $response_data['message'] : __( 'Unknown error from SMTP.com.', 'gravitysmtp' );
					$this->log_failure( $email, $error_message );

					return $email;
				}

				if ( $response_data['status'] === 'fail' ) {
					$error_message = isset( $response_data['data'] ) ? wp_json_encode( $response_data['data'] ) : __( 'Validation failed.', 'gravitysmtp' );
					$this->log_failure( $email, $error_message );

					return $email;
				}
			}

			// Fallback for 2xx without a recognized JSend status key
			if ( $response_code >= 200 && $response_code < 300 ) {
				$this->events->update( array( 'status' => 'sent' ), $email );
				$this->logger->log( $email, 'sent', __( 'Email accepted (HTTP 2xx) but response did not include a JSend status field.', 'gravitysmtp' ) );

				return true;
			}

			$this->log_failure( $email, $response_body );

			return $email;
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
		$atts    = $this->get_send_atts();
		$api_key = $this->get_setting( self::SETTING_API_KEY );
		$channel = $this->get_setting( self::SETTING_CHANNEL, '' );

		$recipients = array( 'to' => $this->build_recipient_list( $atts['to']->as_array() ) );

		if ( ! empty( $atts['headers']['cc'] ) ) {
			$recipients['cc'] = $this->build_recipient_list( $atts['headers']['cc']->as_array() );
		}

		if ( ! empty( $atts['headers']['bcc'] ) ) {
			$recipients['bcc'] = $this->build_recipient_list( $atts['headers']['bcc']->as_array() );
		}

		// Build originator
		$from = array( 'address' => $atts['from']['email'] );

		if ( ! empty( $atts['from']['name'] ) ) {
			$from['name'] = $atts['from']['name'];
		}

		$originator = array( 'from' => $from );

		// Build reply-to
		if ( ! empty( $atts['reply_to'] ) ) {
			if ( isset( $atts['reply_to']['email'] ) ) {
				$reply_to_data = $atts['reply_to'];
			} else {
				$reply_to_data = $atts['reply_to'][0];
			}

			$reply_to_entry = array( 'address' => $reply_to_data['email'] );

			if ( ! empty( $reply_to_data['name'] ) ) {
				$reply_to_entry['name'] = $reply_to_data['name'];
			}

			$originator['reply_to'] = $reply_to_entry;
		}

		// Build body parts
		$is_html = ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false;
		$parts   = array();

		if ( $is_html ) {
			$parts[] = array(
				'type'     => 'text/html',
				'content'  => base64_encode( $atts['message'] ),
				'charset'  => 'utf-8',
				'encoding' => 'base64',
			);

			// Generate plain text fallback
			$text_body = wp_strip_all_tags( $atts['message'] );
			$text_body = preg_replace( "/([\r\n]{2,}|[\n]{2,}|[\r]{2,}|[\r\t]{2,}|[\n\t]{2,})/", "\n", $text_body );

			$parts[] = array(
				'type'     => 'text/plain',
				'content'  => base64_encode( $text_body ),
				'charset'  => 'utf-8',
				'encoding' => 'base64',
			);
		} else {
			$parts[] = array(
				'type'     => 'text/plain',
				'content'  => base64_encode( $atts['message'] ),
				'charset'  => 'utf-8',
				'encoding' => 'base64',
			);
		}

		$body = array( 'parts' => $parts );

		// Build attachments
		if ( ! empty( $atts['attachments'] ) ) {
			$attachment_data = $this->get_attachments( $atts['attachments'] );

			if ( ! empty( $attachment_data ) ) {
				$body['attachments'] = $attachment_data;
			}
		}

		// Assemble the full message payload
		$message = array(
			'channel'    => $channel,
			'recipients' => $recipients,
			'originator' => $originator,
			'subject'    => $atts['subject'],
			'body'       => $body,
		);

		// Build custom headers
		$custom_headers = $this->get_filtered_message_headers();

		if ( ! empty( $custom_headers ) ) {
			$message['custom_headers'] = $custom_headers;
		}

		return array(
			'body'    => wp_json_encode( $message ),
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
	 * Gets a list of attachments, and returns them in a format that can be used by the SMTP.com API.
	 *
	 * @since 1.0
	 *
	 * @param array $attachments The list of attachments.
	 *
	 * @return array Returns an array of attachments formatted for SMTP.com.
	 */
	protected function get_attachments( $attachments ) {
		$data = array();

		foreach ( $attachments as $custom_name => $attachment ) {
			try {
				if ( is_file( $attachment ) && is_readable( $attachment ) ) {
					$file_name = is_numeric( $custom_name ) ? basename( $attachment ) : $custom_name;
					$content   = base64_encode( file_get_contents( $attachment ) );

					$data[] = array(
						'filename' => $file_name,
						'content'  => $content,
						'type'     => mime_content_type( $attachment ),
						'encoding' => 'base64',
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
	 * @param string $api_key The API key for authentication.
	 *
	 * @return array Returns the header array to be passed to SMTP.com's API.
	 */
	protected function get_request_headers( $api_key ) {
		return array(
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		);
	}

	/**
	 * Logs an email send failure.
	 *
	 * @since 1.0
	 *
	 * @param string $email         The email that failed.
	 * @param string $error_message The error message.
	 */
	private function log_failure( $email, $error_message ) {
		$this->events->update( array( 'status' => 'failed' ), $email );
		$this->logger->log( $email, 'failed', $error_message );
	}

	/**
	 * Builds a recipient list formatted for the SMTP.com API.
	 *
	 * @since 2.2.0
	 *
	 * @param array $recipients Raw recipient array with 'email' and optional 'name' keys.
	 *
	 * @return array Formatted recipient list for the API payload.
	 */
	private function build_recipient_list( $recipients ) {
		$list = array();

		foreach ( $recipients as $recipient ) {
			$entry = array( 'address' => $recipient['email'] );

			if ( ! empty( $recipient['name'] ) ) {
				$entry['name'] = $recipient['name'];
			}

			$list[] = $entry;
		}

		return $list;
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
			self::SETTING_API_KEY              => $this->get_setting( self::SETTING_API_KEY, '' ),
			self::SETTING_CHANNEL              => $this->get_setting( self::SETTING_CHANNEL, '' ),
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
	 * @since 1.0
	 *
	 * @return array
	 */
	public function settings_fields() {
		return array(
			'title'       => esc_html__( 'SMTP.com Settings', 'gravitysmtp' ),
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
								'content' => __( 'Enter your SMTP.com API key. You can find this in your SMTP.com dashboard under API Settings.', 'gravitysmtp' ),
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
						'component' => 'Input',
						'props'     => array(
							'labelAttributes'    => array(
								'label'  => esc_html__( 'Channel Name', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								'content' => __( 'Enter your SMTP.com channel name. You can find your channels in the SMTP.com dashboard under Channels.', 'gravitysmtp' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'name'               => self::SETTING_CHANNEL,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_CHANNEL, '' ),
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
	}

	/**
	 * Determine if the API credentials are configured correctly.
	 *
	 * @since 1.0
	 *
	 * @return bool|WP_Error Returns true if configured, or a WP_Error object if not.
	 */
	public function is_configured() {
		$api_key = $this->get_setting( self::SETTING_API_KEY, '' );

		if ( empty( $api_key ) ) {
			$error = new WP_Error( 'missing_api_key', __( 'API key is required.', 'gravitysmtp' ) );
			self::$configured = $error;

			return $error;
		}

		$channel = $this->get_setting( self::SETTING_CHANNEL, '' );

		if ( empty( $channel ) ) {
			$error = new WP_Error( 'missing_channel', __( 'Channel name is required.', 'gravitysmtp' ) );
			self::$configured = $error;

			return $error;
		}

		$response = wp_remote_get(
			$this->url . '/account',
			array(
				'headers' => $this->get_request_headers( $api_key ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error = new WP_Error( 'connection_error', $response->get_error_message() );
			self::$configured = $error;

			return $error;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $response_code === 401 ) {
			$error = new WP_Error( 'invalid_api_key', __( 'Invalid API key.', 'gravitysmtp' ) );
			self::$configured = $error;

			return $error;
		}

		if ( $response_code !== 200 ) {
			$error = new WP_Error( 'connection_error', __( 'Unable to connect to SMTP.com. Please check your credentials.', 'gravitysmtp' ) );
			self::$configured = $error;

			return $error;
		}

		self::$configured = true;

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
		$data['disabled'] = ! Feature_Flag_Manager::is_enabled( 'smtpcom_integration' );

		return $data;
	}
}
