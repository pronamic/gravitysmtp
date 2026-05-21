<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;

class Connector_Sendgrid extends Connector_Base {

	const SETTING_API_KEY      = 'api_key';
	const SETTING_REGION       = 'region';
	const SETTING_IP_POOL_NAME = 'ip_pool_name';

	const OPTION_REGION_GLOBAL = 'global';
	const OPTION_REGION_EU     = 'eu';

	const API_URL_GLOBAL = 'https://api.sendgrid.com/v3/mail/send';
	const API_URL_EU     = 'https://api.eu.sendgrid.com/v3/mail/send';

	protected $name        = 'sendgrid';
	protected $title       = 'SendGrid';
	protected $description = '';
	protected $logo        = 'SendGrid';
	protected $full_logo   = 'SendGridFull';

	public function get_description() {
		return esc_html__( 'Send at scale with Twilio SendGrid, boasting an industry-leading 99% deliverability rate. SendGrid offers both a free-forever plan of 100 emails a day, and, if you need to exceed that limit, a selection of preset pricing plans, starting at $19.95 per month for up to 50,000 emails. For more information on how to get started with SendGrid, read our documentation.', 'gravitysmtp' );
	}

	protected $sensitive_fields = array(
		self::SETTING_API_KEY,
	);

	/**
	 * Get the API URL based on the configured region.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	protected function get_api_url() {
		$region = $this->get_setting( self::SETTING_REGION, self::OPTION_REGION_GLOBAL );
		return $region === self::OPTION_REGION_EU ? self::API_URL_EU : self::API_URL_GLOBAL;
	}

	public function send() {
		try {
			$atts   = $this->get_send_atts();
			$source = $this->get_att( 'source' );
			$params = $this->get_request_params();
			$email  = $this->email;

			$this->set_email_log_data( $atts['subject'], $atts['message'], $atts['to'], empty( $atts['from']['name'] ) ? $atts['from']['email'] : sprintf( '%s <%s>', $atts['from']['name'], $atts['from']['email'] ), $atts['headers'], $atts['attachments'], $source, $params );

			$this->logger->log( $email, 'started', __( 'Starting email send for SendGrid connector.', 'gravitysmtp' ) );

			if ( $this->is_test_mode() ) {
				$this->events->update( array( 'status' => 'sandboxed' ), $email );
				$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );

				return true;
			}

			$response = wp_safe_remote_post( $this->get_api_url(), $params );

			if ( (int) wp_remote_retrieve_response_code( $response ) > 299 ) {
				$this->events->update( array( 'status' => 'failed' ), $email );

				$this->logger->log( $email, 'failed', wp_remote_retrieve_body( $response ) );

				return $email;
			}

			$this->events->update( array( 'status' => 'sent' ), $email );

			$this->logger->log( $email, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );

			return true;

		} catch ( \Exception $e ) {
			$this->events->update( array( 'status' => 'failed' ), $email );
			$this->logger->log( $email, 'failed', $e->getMessage() );

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

		if ( ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false ) {
			$message_type = 'text/html';
		} else {
			$message_type = 'text/plain';
		}

		$body = array(
			'from'             => array( 'email' => $atts['from']['email'], 'name' => $atts['from']['name'] ),
			'personalizations' => $this->get_recipients( $atts['to'], $atts['headers'] ),
			'subject'          => $atts['subject'],
			'content'          => array(
				array(
					'value' => $atts['message'],
					'type'  => $message_type,
				),
			),
		);


		if ( ! empty( $atts['reply_to'] ) ) {
			$body['reply_to_list'] = $atts['reply_to'];
		}

		if ( ! empty( $atts['attachments'] ) ) {
			$body['attachments'] = $this->get_attachments( $atts['attachments'] );
		}

		$additional_headers = $this->get_filtered_message_headers();

		if ( ! empty( $additional_headers ) ) {
			$body['headers'] = $additional_headers;
		}

		$ip_pool_name = trim( $this->get_setting( self::SETTING_IP_POOL_NAME, '' ) );
		if ( ! empty( $ip_pool_name ) ) {
			$body['ip_pool_name'] = $ip_pool_name;
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

	protected function get_recipients( $to, $headers ) {

		$recipients = array(
			'to' => $to->as_array(),
		);

		if ( ! empty( $headers['cc'] ) ) {
			$recipients['cc'] = $headers['cc']->as_array();
		}

		if ( ! empty( $headers['bcc'] ) ) {
			$recipients['bcc'] = $headers['bcc']->as_array();
		}

		$recipients = array_filter( $recipients );

		return array( $recipients );
	}

	protected function get_request_headers( $api_key ) {
		return array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		);
	}

	protected function get_attachments( $attachments ) {
		$data = array();

		foreach ( $attachments as $custom_name => $attachment ) {
			$file = false;

			try {
				if ( is_file( $attachment ) && is_readable( $attachment ) ) {
					$fileName  = is_numeric( $custom_name ) ? basename( $attachment ) : $custom_name;
					$contentId = wp_hash( $attachment );
					$file      = file_get_contents( $attachment );
					$mimeType  = mime_content_type( $attachment );
					$filetype  = str_replace( ';', '', trim( $mimeType ) );
				}
			} catch ( \Exception $e ) {
				$file = false;
			}

			if ( $file === false ) {
				continue;
			}

			$data[] = array(
				'type'        => $filetype,
				'filename'    => $fileName,
				'disposition' => 'attachment',
				'content_id'  => $contentId,
				'content'     => base64_encode( $file ),
			);
		}

		return $data;
	}

	public function connector_data() {
		return array(
			self::SETTING_API_KEY              => $this->get_setting( self::SETTING_API_KEY, '' ),
			self::SETTING_REGION               => $this->get_setting( self::SETTING_REGION, self::OPTION_REGION_GLOBAL ),
			self::SETTING_IP_POOL_NAME         => $this->get_setting( self::SETTING_IP_POOL_NAME, '' ),
			self::SETTING_FROM_EMAIL           => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
			self::SETTING_FORCE_FROM_EMAIL     => $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
			self::SETTING_FROM_NAME            => $this->get_setting( self::SETTING_FROM_NAME, '' ),
			self::SETTING_FORCE_FROM_NAME      => $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
			self::SETTING_REPLY_TO_EMAIL       => $this->get_setting( self::SETTING_REPLY_TO_EMAIL, '' ),
			self::SETTING_FORCE_REPLY_TO_EMAIL => $this->get_setting( self::SETTING_FORCE_REPLY_TO_EMAIL, false ),
		);
	}

	public function settings_fields() {
		return array(
			'title'       => esc_html__( 'SendGrid Settings', 'gravitysmtp' ),
			'description' => '',
			'fields'      => array_merge(
				array(
					array(
						'component' => 'Heading',
						'props'     => array(
							'content' => esc_html__( 'Configuration', 'gravitysmtp' ),
							'size'    => 'text-sm',
							'spacing' => [ 4, 0, 4, 0 ],
							'tagName' => 'h3',
							'type'    => 'boxed',
							'weight'  => 'medium',
						),
					),
					array(
						'component' => 'Input',
						'props'     => array(
							'labelAttributes'    => array(
								'label'  => esc_html__( 'SendGrid API Key', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'To obtain an API key from SendGrid you will need to %1$sgenerate an API key%2$s. To send emails, the API key only requires \'Mail Send\' access.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://app.sendgrid.com/settings/api_keys" target="_blank" rel="noopener noreferrer">', '</a>' ),
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
					'component' => 'Box',
					'props'     => array(
						'display' => 'block',
						'spacing' => 6,
					),
					'fields'    => array(
						array(
							'component' => 'Label',
							'props'     => array(
								'label'   => __( 'Region', 'gravitysmtp' ),
								'weight'  => 'medium',
								'size'    => 'text-sm',
								'spacing' => 2,
							),
						),
						array(
							'component' => 'InputGroup',
							'props'     => array(
								'customAttributes' => array(
									'style' => array(
										'display' => 'flex',
									),
								),
								'id'               => self::SETTING_REGION . '_group',
								'initialValue'     => $this->get_setting( self::SETTING_REGION, self::OPTION_REGION_GLOBAL ),
								'inputType'        => 'radio',
								'spacing'          => 2,
								'data'             => array(
									array(
										'id'              => self::SETTING_REGION . '_' . self::OPTION_REGION_GLOBAL,
										'name'            => self::SETTING_REGION,
										'value'           => self::OPTION_REGION_GLOBAL,
										'size'            => 'size-md',
										'spacing'         => array( 0, 4, 0, 0 ),
										'labelAttributes' => array(
											'label'  => __( 'Global', 'gravitysmtp' ),
											'size'   => 'text-sm',
											'weight' => 'regular',
										),
									),
									array(
										'id'              => self::SETTING_REGION . '_' . self::OPTION_REGION_EU,
										'name'            => self::SETTING_REGION,
										'value'           => self::OPTION_REGION_EU,
										'size'            => 'size-md',
										'spacing'         => array( 0, 4, 0, 0 ),
										'labelAttributes' => array(
											'label'  => __( 'EU', 'gravitysmtp' ),
											'size'   => 'text-sm',
											'weight' => 'regular',
										),
									),
								),
							),
						),
						array(
							'component' => 'Text',
							'props'     => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'Choose your SendGrid API region. Select EU for European data residency. Important: the EU region requires an API key created under an EU subuser. For more information, visit %1$sSendGrid Data Residency%2$s.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://www.twilio.com/docs/sendgrid/data-residency" target="_blank" rel="noopener noreferrer">', '</a>' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
						),
					),
				),
					array(
						'component' => 'Input',
						'props'     => array(
							'labelAttributes'    => array(
								'label'  => esc_html__( 'IP Pool Name', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'Optional. Enter the name of a SendGrid IP pool to route emails through specific IPs (e.g. for EU/GDPR compliance). The pool must already exist in your SendGrid account. For more information, see %1$sSendGrid IP Pools%2$s.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://docs.sendgrid.com/ui/account-and-settings/ip-pools" target="_blank" rel="noopener noreferrer">', '</a>' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'name'               => self::SETTING_IP_POOL_NAME,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_IP_POOL_NAME, '' ),
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

	public function migration_map() {
		return array(
			array(
				'original_key' => 'gravityformsaddon_gravityformssendgrid_settings',
				'sub_key'      => 'apiKey',
				'new_key'      => self::SETTING_API_KEY,
			),
		);
	}

	/**
	 * Verify the API key with the API.
	 *
	 * @since 1.0
	 *
	 * @return true|\WP_Error
	 */
	private function verify_api_key() {
		$api_key = $this->get_setting( self::SETTING_API_KEY );
		$url     = str_replace( 'mail/send', 'scopes', $this->get_api_url() );

		if ( empty( $api_key ) ) {
			return new \WP_Error( 'missing_api_key', __( 'No API Key provided.', 'gravitysmtp' ) );
		}

		$response = wp_remote_get(
			$url,
			array(
				'headers' => $this->get_request_headers( $api_key ),
			)
		);

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code != '200' ) {
			return new \WP_Error( 'invalid_api_key', __( 'Invalid API Key provided.', 'gravitysmtp' ) );
		}

		$scopes = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! in_array( 'mail.send', $scopes['scopes'] ) ) {
			return new \WP_Error( 'insufficient_permissions', __( 'API Key does not have permission to send mail.', 'gravitysmtp' ) );
		}

		return true;
	}

	/**
	 * Determine if the API credentials are configured correctly.
	 *
	 * @since 1.0
	 *
	 * @return bool|\WP_Error
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

}
