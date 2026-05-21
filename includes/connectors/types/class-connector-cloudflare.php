<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Exception;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use WP_Error;

/**
 * Connector for Cloudflare Email Service.
 *
 * @since 2.2.0
 */
class Connector_Cloudflare extends Connector_Base {

	const SETTING_ACCOUNT_ID = 'account_id';
	const SETTING_API_TOKEN  = 'api_token';

	protected $name        = 'cloudflare';
	protected $title       = 'Cloudflare';
	protected $description = '';
	protected $logo        = 'Cloudflare';
	protected $full_logo   = 'CloudflareFull';
	protected $url         = 'https://api.cloudflare.com/client/v4/accounts/%s/email/sending/send';

	protected $sensitive_fields = array(
		self::SETTING_API_TOKEN,
	);

	/**
	 * Get the connector description.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'Send transactional email through Cloudflare Email Service using a domain managed on Cloudflare DNS. Requires a paid Cloudflare plan.', 'gravitysmtp' );
	}

	/**
	 * Send the email through Cloudflare.
	 *
	 * @since 2.2.0
	 *
	 * @return bool|int
	 */
	public function send() {
		$email  = $this->email;
		$atts   = $this->get_send_atts();
		$source = $this->get_att( 'source' );
		/** @var Event_Model $events */
		$events = $this->events;

		try {
			$request_body = $this->get_request_body( $atts );

			if ( is_wp_error( $request_body ) ) {
				return $this->log_failure( $email, $request_body->get_error_message() );
			}

			$params = $this->get_request_params_for_body( $request_body );

			$this->set_email_log_data( $atts['subject'], $atts['message'], $atts['to'], $atts['from']['from'], $atts['headers'], $atts['attachments'], $source, $params );

			$this->logger->log( $email, 'started', __( 'Starting email send for Cloudflare connector.', 'gravitysmtp' ) );
			$this->debug_logger->log_debug(
				$this->wrap_debug_with_details(
					__FUNCTION__,
					$email,
					sprintf(
						'Starting Cloudflare send with %1$d recipient(s) and payload size %2$d bytes.',
						$this->get_recipient_count( $atts ),
						strlen( $params['body'] )
					)
				)
			);

			if ( $this->is_test_mode() ) {
				$events->update( array( 'status' => 'sandboxed' ), $email );
				$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );
				$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Test mode is enabled, sandboxing email.' ) );

				return true;
			}

			$response = wp_safe_remote_post( $this->get_send_url(), $params );

			if ( is_wp_error( $response ) ) {
				return $this->log_failure( $email, $response->get_error_message() );
			}

			$response_code = (int) wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			$decoded_body  = json_decode( $response_body, true );

			$this->debug_logger->log_debug(
				$this->wrap_debug_with_details(
					__FUNCTION__,
					$email,
					sprintf( 'Cloudflare response (%1$d): %2$s', $response_code, $response_body )
				)
			);

			if ( ! $this->is_successful_response( $response_code, $decoded_body ) ) {
				return $this->log_failure( $email, $this->get_api_error_message( $response_code, $decoded_body, $response_body ) );
			}

			$events->update( array( 'status' => 'sent' ), $email );
			$this->logger->log( $email, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );
			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email successfully sent.' ) );

			return true;
		} catch ( Exception $e ) {
			return $this->log_failure( $email, $e->getMessage() );
		}
	}

	/**
	 * Get the request parameters for sending email through Cloudflare.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	public function get_request_params() {
		$request_body = $this->get_request_body( $this->get_send_atts() );

		if ( is_wp_error( $request_body ) ) {
			return array();
		}

		return $this->get_request_params_for_body( $request_body );
	}

	/**
	 * Get the request parameters for a prepared Cloudflare body.
	 *
	 * @since 2.2.0
	 *
	 * @param array $request_body The request body.
	 *
	 * @return array
	 */
	protected function get_request_params_for_body( $request_body ) {
		return array(
			'body'    => wp_json_encode( $request_body ),
			'headers' => $this->get_request_headers( trim( (string) $this->get_setting( self::SETTING_API_TOKEN, '' ) ) ),
		);
	}

	/**
	 * Get the attributes for sending email.
	 *
	 * @since 2.2.0
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
	 * Build the Cloudflare request body.
	 *
	 * @since 2.2.0
	 *
	 * @param array $atts The send attributes.
	 *
	 * @return array|WP_Error
	 */
	protected function get_request_body( $atts ) {
		$account_id = trim( (string) $this->get_setting( self::SETTING_ACCOUNT_ID, '' ) );
		$api_token  = trim( (string) $this->get_setting( self::SETTING_API_TOKEN, '' ) );

		if ( empty( $account_id ) ) {
			return new WP_Error( 'missing_account_id', __( 'No Account ID provided.', 'gravitysmtp' ) );
		}

		if ( empty( $api_token ) ) {
			return new WP_Error( 'missing_api_token', __( 'No API Token provided.', 'gravitysmtp' ) );
		}

		$request_body = array(
			'from'    => $this->format_address( $atts['from'] ),
			'to'      => $this->format_recipients( $atts['to'] ),
			'subject' => $atts['subject'],
		);

		$is_html = ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false;

		if ( $is_html ) {
			$request_body['html'] = $atts['message'];
			$request_body['text'] = $this->get_text_body( $atts['message'] );
		} else {
			$request_body['text'] = $atts['message'];
		}

		if ( empty( $request_body['html'] ) && empty( $request_body['text'] ) ) {
			return new WP_Error( 'missing_email_body', __( 'Cloudflare requires either an HTML or plain text email body.', 'gravitysmtp' ) );
		}

		if ( ! empty( $atts['headers']['cc'] ) ) {
			$request_body['cc'] = $this->format_recipients( $atts['headers']['cc'] );
		}

		if ( ! empty( $atts['headers']['bcc'] ) ) {
			$request_body['bcc'] = $this->format_recipients( $atts['headers']['bcc'] );
		}

		if ( ! empty( $atts['reply_to'] ) ) {
			$reply_to = isset( $atts['reply_to'][0] ) ? $atts['reply_to'][0] : $atts['reply_to'];

			if ( ! empty( $reply_to['email'] ) ) {
				$request_body['reply_to'] = $this->format_address( $reply_to );
			}
		}

		$additional_headers = $this->get_filtered_message_headers();

		if ( ! empty( $additional_headers ) ) {
			$request_body['headers'] = array_map( 'strval', $additional_headers );
		}

		if ( ! empty( $atts['attachments'] ) ) {
			$attachments = $this->get_attachments( $atts['attachments'] );

			if ( ! empty( $attachments ) ) {
				$request_body['attachments'] = $attachments;
			}
		}

		return $request_body;
	}

	/**
	 * Format a recipient collection for Cloudflare.
	 *
	 * @since 2.2.0
	 *
	 * @param object $recipients The parsed recipient collection.
	 *
	 * @return array
	 */
	protected function format_recipients( $recipients ) {
		$formatted = array();

		foreach ( $recipients->as_array() as $recipient ) {
			$formatted[] = $this->format_address( $recipient );
		}

		return $formatted;
	}

	/**
	 * Format an address array for Cloudflare.
	 *
	 * @since 2.2.0
	 *
	 * @param array $address The address data.
	 *
	 * @return array|string
	 */
	protected function format_address( $address ) {
		if ( ! empty( $address['name'] ) ) {
			$name = $address['name'];

			// RFC 5322: quote display names containing specials so they aren't
			// mis-parsed (e.g. parentheses treated as comments by Cloudflare).
			if ( preg_match( '/[()<>\[\]:;@\\\\",.]/', $name ) ) {
				$name = '"' . str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $name ) . '"';
			}

			return sprintf( '%s <%s>', $name, $address['email'] );
		}

		return $address['email'];
	}

	/**
	 * Convert HTML content into a text fallback.
	 *
	 * @since 2.2.0
	 *
	 * @param string $message The message body.
	 *
	 * @return string
	 */
	protected function get_text_body( $message ) {
		$text_body = wp_strip_all_tags( $message );

		return preg_replace( "/([\r\n]{2,}|[\n]{2,}|[\r]{2,}|[\r\t]{2,}|[\n\t]{2,})/", "\n", $text_body );
	}

	/**
	 * Get attachments formatted for Cloudflare.
	 *
	 * @since 2.2.0
	 *
	 * @param array $attachments The attachments array.
	 *
	 * @return array
	 */
	protected function get_attachments( $attachments ) {
		$data = array();

		foreach ( $attachments as $custom_name => $attachment ) {
			$file = false;

			try {
				if ( is_file( $attachment ) && is_readable( $attachment ) ) {
					$file_name  = is_numeric( $custom_name ) ? basename( $attachment ) : $custom_name;
					$content_id = wp_hash( $attachment );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local attachments must be base64 encoded for API connectors.
					$file      = file_get_contents( $attachment );
					$mime_type = mime_content_type( $attachment );
					$file_type = str_replace( ';', '', trim( $mime_type ) );
				}
			} catch ( Exception $e ) {
				$file = false;
			}

			if ( $file === false ) {
				continue;
			}

			$data[] = array(
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Local attachments must be base64 encoded for Cloudflare's API.
				'content'     => base64_encode( $file ),
				'disposition' => 'attachment',
				'filename'    => $file_name,
				'type'        => $file_type,
				'content_id'  => $content_id,
			);
		}

		return $data;
	}

	/**
	 * Determine if the Cloudflare response is successful.
	 *
	 * @since 2.2.0
	 *
	 * @param int        $response_code The HTTP response code.
	 * @param array|null $decoded_body  The decoded response body.
	 *
	 * @return bool
	 */
	protected function is_successful_response( $response_code, $decoded_body ) {
		if ( $response_code >= 300 ) {
			return false;
		}

		if ( ! is_array( $decoded_body ) ) {
			return false;
		}

		return ! empty( $decoded_body['success'] );
	}

	/**
	 * Build an actionable API error message.
	 *
	 * @since 2.2.0
	 *
	 * @param int        $response_code The HTTP response code.
	 * @param array|null $decoded_body  The decoded response body.
	 * @param string     $response_body The raw response body.
	 *
	 * @return string
	 */
	protected function get_api_error_message( $response_code, $decoded_body, $response_body ) {
		$parts = array();

		if ( 429 === $response_code ) {
			$parts[] = __( 'Cloudflare rate limit exceeded.', 'gravitysmtp' );
		} elseif ( $response_code >= 300 ) {
			$parts[] = sprintf(
				/* translators: %d: HTTP response code. */
				__( 'Cloudflare API request failed with status code %d.', 'gravitysmtp' ),
				$response_code
			);
		} else {
			$parts[] = __( 'Cloudflare API reported an unsuccessful response.', 'gravitysmtp' );
		}

		if ( is_array( $decoded_body ) ) {
			$error_parts = array();

			if ( ! empty( $decoded_body['errors'] ) && is_array( $decoded_body['errors'] ) ) {
				foreach ( $decoded_body['errors'] as $error ) {
					$code    = isset( $error['code'] ) ? $error['code'] : '';
					$message = isset( $error['message'] ) ? $error['message'] : '';

					if ( empty( $message ) ) {
						continue;
					}

					$error_parts[] = empty( $code ) ? $message : sprintf( '[%1$s] %2$s', $code, $message );
				}
			}

			if ( ! empty( $decoded_body['messages'] ) && is_array( $decoded_body['messages'] ) ) {
				foreach ( $decoded_body['messages'] as $message ) {
					$message_text = isset( $message['message'] ) ? $message['message'] : '';

					if ( empty( $message_text ) ) {
						continue;
					}

					$error_parts[] = $message_text;
				}
			}

			if ( ! empty( $error_parts ) ) {
				$parts[] = implode( ' ', $error_parts );
			}
		}

		if ( empty( $decoded_body ) && ! empty( $response_body ) ) {
			$parts[] = $response_body;
		}

		return implode( ' ', array_filter( $parts ) );
	}

	/**
	 * Get the send URL.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	protected function get_send_url() {
		return sprintf( $this->url, trim( (string) $this->get_setting( self::SETTING_ACCOUNT_ID, '' ) ) );
	}

	/**
	 * Get the recipient count for the message.
	 *
	 * @since 2.2.0
	 *
	 * @param array $atts The send attributes.
	 *
	 * @return int
	 */
	protected function get_recipient_count( $atts ) {
		$count = count( $atts['to']->as_array() );

		if ( ! empty( $atts['headers']['cc'] ) ) {
			$count += count( $atts['headers']['cc']->as_array() );
		}

		if ( ! empty( $atts['headers']['bcc'] ) ) {
			$count += count( $atts['headers']['bcc']->as_array() );
		}

		return $count;
	}

	/**
	 * Get the headers to be used in the API request.
	 *
	 * @since 2.2.0
	 *
	 * @param string $api_token The Cloudflare API token.
	 *
	 * @return array
	 */
	protected function get_request_headers( $api_token ) {
		return array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_token,
		);
	}

	/**
	 * Log an email send failure.
	 *
	 * @since 2.2.0
	 *
	 * @param int    $email         The failed email event ID.
	 * @param string $error_message The error message.
	 *
	 * @return int
	 */
	protected function log_failure( $email, $error_message ) {
		/** @var Event_Model $events */
		$events = $this->events;

		$events->update( array( 'status' => 'failed' ), $email );
		$this->logger->log( $email, 'failed', $error_message );
		$this->debug_logger->log_error( $this->wrap_debug_with_details( __FUNCTION__, $email, $error_message ) );

		return $email;
	}

	/**
	 * Connector data.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	public function connector_data() {
		return array(
			self::SETTING_ACCOUNT_ID           => $this->get_setting( self::SETTING_ACCOUNT_ID, '' ),
			self::SETTING_API_TOKEN            => $this->get_setting( self::SETTING_API_TOKEN, '' ),
			self::SETTING_FROM_EMAIL           => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
			self::SETTING_FORCE_FROM_EMAIL     => $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
			self::SETTING_FROM_NAME            => $this->get_setting( self::SETTING_FROM_NAME, '' ),
			self::SETTING_FORCE_FROM_NAME      => $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
			self::SETTING_REPLY_TO_EMAIL       => $this->get_setting( self::SETTING_REPLY_TO_EMAIL, '' ),
			self::SETTING_FORCE_REPLY_TO_EMAIL => $this->get_setting( self::SETTING_FORCE_REPLY_TO_EMAIL, false ),
		);
	}

	/**
	 * Get the unique data for this connector, merged with the default/common data for all
	 * connectors in the system.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	protected function get_merged_data() {
		$data             = parent::get_merged_data();
		$data['disabled'] = ! Feature_Flag_Manager::is_enabled( 'cloudflare_integration' );

		return $data;
	}

	/**
	 * Settings fields.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	public function settings_fields() {
		return array(
			'title'       => esc_html__( 'Cloudflare Settings', 'gravitysmtp' ),
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
								'label'  => esc_html__( 'Account ID', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'Copy the Account ID from your Cloudflare dashboard. Your Default From Email must use a domain managed on Cloudflare DNS and onboarded in Cloudflare Email Service for Email Sending. Cloudflare adds the required sending records on the cf-bounce subdomain plus your domain DMARC record. See Cloudflare\'s %1$sdomain configuration docs%2$s for setup details.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://developers.cloudflare.com/email-service/configuration/domains/" target="_blank" rel="noopener noreferrer">', '</a>' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'name'               => self::SETTING_ACCOUNT_ID,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_ACCOUNT_ID, '' ),
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
								'content' => sprintf( __( 'Create a Cloudflare API token with access to Cloudflare Email Service for the target account. For full setup verification, also grant Zone Read and Email Sending Read permissions. Cloudflare Email Service currently requires a paid Cloudflare plan. See Cloudflare\'s %1$semail sending docs%2$s for setup guidance.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://developers.cloudflare.com/email-service/api/send-emails/rest-api/" target="_blank" rel="noopener noreferrer">', '</a>' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'name'               => self::SETTING_API_TOKEN,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_API_TOKEN, '' ),
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
						'component' => 'Text',
						'props'     => array(
							'content' => esc_html__( 'Set your Default From Email to a mailbox on a domain managed on Cloudflare DNS and onboarded in Cloudflare Email Service for Email Sending.', 'gravitysmtp' ),
							'size'    => 'text-xs',
							'spacing' => 6,
							'weight'  => 'regular',
						),
					),
				),
				$this->get_from_settings_fields(),
				$this->get_reply_to_settings_fields(),
			),
		);
	}

	/**
	 * Determine if the Cloudflare connector is configured.
	 *
	 * Validates credentials are present, then checks the Cloudflare API to
	 * confirm the From Email domain is an active zone on this account with
	 * Email Sending enabled.
	 *
	 * @since 2.2.0
	 *
	 * @return bool|WP_Error
	 */
	public function is_configured() {
		$account_id = trim( (string) $this->get_setting( self::SETTING_ACCOUNT_ID, '' ) );
		$api_token  = trim( (string) $this->get_setting( self::SETTING_API_TOKEN, '' ) );
		$from_email = trim( (string) $this->get_setting( self::SETTING_FROM_EMAIL, '' ) );

		if ( empty( $account_id ) ) {
			return new WP_Error( 'missing_account_id', __( 'No Account ID provided.', 'gravitysmtp' ) );
		}

		if ( empty( $api_token ) ) {
			return new WP_Error( 'missing_api_token', __( 'No API Token provided.', 'gravitysmtp' ) );
		}

		if ( empty( $from_email ) ) {
			return new WP_Error( 'missing_from_email', __( 'No Default From Email provided. Cloudflare requires a From Email on a domain managed in your Cloudflare account.', 'gravitysmtp' ) );
		}

		if ( ! is_email( $from_email ) ) {
			return new WP_Error( 'invalid_from_email', __( 'The Default From Email is not a valid email address.', 'gravitysmtp' ) );
		}

		$from_domain = $this->get_email_domain( $from_email );

		if ( empty( $from_domain ) ) {
			return new WP_Error( 'invalid_from_domain', __( 'Could not determine a domain from the Default From Email.', 'gravitysmtp' ) );
		}

		$zone_id = $this->get_zone_id_for_domain( $from_domain, $account_id, $api_token );

		if ( is_wp_error( $zone_id ) ) {
			return $zone_id;
		}

		$subdomains = $this->get_sending_subdomains( $zone_id, $api_token );

		if ( is_wp_error( $subdomains ) ) {
			return new WP_Error(
				'sending_status_check_failed',
				sprintf(
					/* translators: %s: sending domain. */
					__( 'Could not verify Email Sending status for %s. Make sure your API token has Email Sending read permissions.', 'gravitysmtp' ),
					$from_domain
				)
			);
		}

		if ( empty( $subdomains ) ) {
			return new WP_Error(
				'no_sending_subdomains',
				sprintf(
					/* translators: %s: sending domain. */
					__( 'No sending subdomains are configured for %s. Onboard your domain in Cloudflare Email Service for Email Sending.', 'gravitysmtp' ),
					$from_domain
				)
			);
		}

		$has_enabled = false;

		foreach ( $subdomains as $subdomain ) {
			if ( ! empty( $subdomain['enabled'] ) ) {
				$has_enabled = true;
				break;
			}
		}

		if ( ! $has_enabled ) {
			return new WP_Error(
				'sending_not_enabled',
				sprintf(
					/* translators: %s: sending domain. */
					__( 'Email Sending is not yet enabled for %s. Complete the domain onboarding in Cloudflare Email Service so that the required DNS records are verified.', 'gravitysmtp' ),
					$from_domain
				)
			);
		}

		return true;
	}

	/**
	 * Get the domain portion of an email address.
	 *
	 * @since 2.2.0
	 *
	 * @param string $email The email address.
	 *
	 * @return string
	 */
	protected function get_email_domain( $email ) {
		$at_position = strrpos( $email, '@' );

		if ( $at_position === false ) {
			return '';
		}

		return strtolower( substr( $email, $at_position + 1 ) );
	}

	/**
	 * Resolve the Cloudflare zone ID for a domain on this account.
	 *
	 * @since 2.2.0
	 *
	 * @param string $domain     The sending domain.
	 * @param string $account_id The Cloudflare account ID.
	 * @param string $api_token  The Cloudflare API token.
	 *
	 * @return string|WP_Error The zone ID, or WP_Error on failure.
	 */
	protected function get_zone_id_for_domain( $domain, $account_id, $api_token ) {
		$url = add_query_arg(
			array(
				'name'       => $domain,
				'account.id' => $account_id,
			),
			'https://api.cloudflare.com/client/v4/zones'
		);

		$response = $this->cloudflare_api_get( $url, $api_token );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['result'] ) || ! is_array( $response['result'] ) ) {
			return new WP_Error( 'zone_not_found', __( 'No matching zone found on this Cloudflare account.', 'gravitysmtp' ) );
		}

		$zone = $response['result'][0];

		if ( empty( $zone['id'] ) ) {
			return new WP_Error( 'zone_not_found', __( 'No matching zone found on this Cloudflare account.', 'gravitysmtp' ) );
		}

		if ( isset( $zone['status'] ) && 'active' !== $zone['status'] ) {
			return new WP_Error(
				'zone_not_active',
				sprintf(
					/* translators: %s: zone status. */
					__( 'Zone exists but is not active (status: %s).', 'gravitysmtp' ),
					$zone['status']
				)
			);
		}

		return $zone['id'];
	}

	/**
	 * Get the sending subdomains for a zone.
	 *
	 * @since 2.2.0
	 *
	 * @param string $zone_id   The Cloudflare zone ID.
	 * @param string $api_token The Cloudflare API token.
	 *
	 * @return array|WP_Error The subdomains array, or WP_Error on failure.
	 */
	protected function get_sending_subdomains( $zone_id, $api_token ) {
		$url      = sprintf( 'https://api.cloudflare.com/client/v4/zones/%s/email/sending/subdomains', $zone_id );
		$response = $this->cloudflare_api_get( $url, $api_token );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['result'] ) || ! is_array( $response['result'] ) ) {
			return array();
		}

		return $response['result'];
	}

	/**
	 * Make a GET request to the Cloudflare API v4.
	 *
	 * @since 2.2.0
	 *
	 * @param string $url       The full API URL.
	 * @param string $api_token The Cloudflare API token.
	 *
	 * @return array|WP_Error The decoded response body, or WP_Error on failure.
	 */
	protected function cloudflare_api_get( $url, $api_token ) {
		$response = wp_safe_remote_get(
			$url,
			array(
				'headers' => $this->get_request_headers( $api_token ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$decoded_body  = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $this->is_successful_response( $response_code, $decoded_body ) ) {
			return new WP_Error(
				'cloudflare_api_error',
				$this->get_api_error_message( $response_code, $decoded_body, wp_remote_retrieve_body( $response ) )
			);
		}

		return $decoded_body;
	}
}
