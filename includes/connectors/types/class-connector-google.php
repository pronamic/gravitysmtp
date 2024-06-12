<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Google\Client;
use Google\Service\Gmail;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Oauth\Google_Oauth_Handler;
use Gravity_Forms\Gravity_SMTP\Connectors\Oauth_Handler;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;

/**
 * Connector for Google / Gmail
 *
 * @since 1.0
 */
class Connector_Google extends Connector_Base {

	const SETTING_ACCESS_TOKEN  = 'access_token';
	const SETTING_CLIENT_ID     = 'client_id';
	const SETTING_CLIENT_SECRET = 'client_secret';

	const VALUE_REDIRECT_URI = 'redirect_uri';

	protected $name        = 'google';
	protected $title       = 'Google / Gmail';
	protected $disabled    = true;
	protected $description = '';
	protected $logo        = 'Google';
	protected $full_logo   = 'GoogleFull';

	protected $oauth_handler;

	public function init( $to, $subject, $message, $headers = '', $attachments = array(), $source = '' ) {
		parent::init( $to, $subject, $message, $headers, $attachments, $source );

		$this->oauth_handler = \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::container()->get( Connector_Service_Provider::GOOGLE_OAUTH_HANDLER );
	}

	public function get_description() {
		return esc_html__( 'Integrate your website with Gmail or a Google Workspace account, helping to improve email deliverability and prevent your carefully crafted content from ending up in spam folders. Be sure to check the email sending limits for Gmail and Google Workspace. For more information on how to get started with Gmail / Google Workspace, read our documentation.', 'gravitysmtp' );
	}

	/**
	 * Sending logic.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function send() {
		$to          = $this->get_att( 'to', '' );
		$subject     = $this->get_att( 'subject', '' );
		$message     = $this->get_att( 'message', '' );
		$headers     = $this->get_parsed_headers( $this->get_att( 'headers', array() ) );
		$attachments = $this->get_att( 'attachments', array() );
		$from        = $this->get_from( true );
		$reply_to    = $this->get_reply_to( true );
		$source      = $this->get_att( 'source' );
		$params      = $this->get_request_params();

		if ( ! empty( $headers['content-type'] ) ) {
			$headers['content-type'] = $this->get_att( 'content_type', $headers['content-type'] );
		}

		$email = $this->events->create(
			$this->name,
			'pending',
			$to,
			empty( $from['name'] ) ? $from['email'] : sprintf( '%s <%s>', $from['name'], $from['email'] ),
			$subject,
			$message,
			array(
				'headers'     => $headers,
				'attachments' => $attachments,
				'source'      => $source,
				'params'      => $params,
			)
		);

		$this->logger->log( $email, 'started', __( 'Starting email send for Google connector.', 'gravitysmtp' ) );

		$this->php_mailer->setFrom( $from['email'], $from['name'] );

		foreach( $to->recipients() as $recipient ) {
			if ( ! empty( $recipient->name() ) ) {
				$this->php_mailer->addAddress( $recipient->email(), $recipient->name() );
			} else {
				$this->php_mailer->addAddress( $recipient->email() );
			}
		}

		$this->php_mailer->Subject = $subject;

		$this->php_mailer->Body = $message;

		if ( ! empty( $headers['cc'] ) ) {
			foreach ( $headers['cc']->recipients() as $recipient ) {
				if ( ! empty( $recipient->name() ) ) {
					$this->php_mailer->addCC( $recipient->email(), $recipient->name() );
				} else {
					$this->php_mailer->addCC( $recipient->email() );
				}
			}
		}

		if ( ! empty( $headers['bcc'] ) ) {
			foreach ( $headers['bcc']->recipients() as $recipient ) {
				if ( ! empty( $recipient->name() ) ) {
					$this->php_mailer->addBCC( $recipient->email(), $recipient->name() );
				} else {
					$this->php_mailer->addBCC( $recipient->email() );
				}
			}
		}

		if ( ! empty( $attachments ) ) {
			foreach ( $attachments as $attachment ) {
				$this->php_mailer->addAttachment( $attachment );
			}
		}

		if ( ! empty( $reply_to ) ) {
			if ( isset( $reply_to['name'] ) ) {
				$this->php_mailer->addReplyTo( $reply_to['email'], $reply_to['name'] );
			} else {
				$this->php_mailer->addReplyTo( $reply_to['email'] );
			}
		}

		if ( ! empty( $headers['content-type'] ) && strpos( $headers['content-type'], 'text/html' ) !== false ) {
			$this->php_mailer->isHTML( true );
		} else {
			$this->php_mailer->isHTML( false );
			$this->php_mailer->ContentType = 'text/plain';
		}

		$additional_headers = $this->get_filtered_message_headers();

		if ( ! empty( $additional_headers ) ) {
			foreach ( $additional_headers as $key => $value ) {
				$this->php_mailer->addCustomHeader( $key, $value );
			}
		}

		if ( $this->is_test_mode() ) {
			$this->events->update( array( 'status' => 'sandboxed' ), $email );
			$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );

			return true;
		}

		$raw = $this->get_raw_message();

		try {
			$body = array(
				'raw' => $raw,
			);

			$url = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

			$headers = array(
				'Authorization' => 'Bearer ' . $this->get_setting( self::SETTING_ACCESS_TOKEN ),
				'Content-Type'  => 'application/json',
			);

			$args = array(
				'body' => json_encode( $body ),
				'headers' => $headers,
			);

			$response      = wp_remote_post( $url, $args );
			$response_body = wp_remote_retrieve_body( $response );
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( (int) $response_code !== 200 ) {
				$this->events->update( array( 'status' => 'failed' ), $email );

				$this->logger->log( $email, 'failed', $e->getMessage() );

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

	private function get_raw_message() {
		$this->php_mailer->preSend();
		$raw    = $this->php_mailer->getSentMIMEMessage();
		return str_replace(
			[ '+', '/', '=' ],
			[ '-', '_', '' ],
			base64_encode( $raw )
		);
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
			self::SETTING_CLIENT_ID        => $this->get_setting( self::SETTING_CLIENT_ID, '' ),
			self::SETTING_CLIENT_SECRET    => $this->get_setting( self::SETTING_CLIENT_SECRET, '' ),
			self::SETTING_ACCESS_TOKEN     => $this->get_setting( self::SETTING_ACCESS_TOKEN, '' ),
			self::SETTING_FROM_EMAIL       => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
			self::SETTING_FORCE_FROM_EMAIL => $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
			self::SETTING_FROM_NAME        => $this->get_setting( self::SETTING_FROM_NAME, '' ),
			self::SETTING_FORCE_FROM_NAME  => $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
			'oauth_url'                    => 'https://accounts.google.com/o/oauth2/v2/auth',
			'oauth_params'                 => '&' . $this->get_oauth_params(),
		);
	}

	protected function get_oauth_params() {
		/**
		 * @var Google_Oauth_Handler $oauth_handler
		 */
		$oauth_handler = Gravity_SMTP::container()->get( Connector_Service_Provider::GOOGLE_OAUTH_HANDLER );

		$params = array(
			'response_type'          => 'code',
			'redirect_uri'           => urldecode( $oauth_handler->get_return_url() ),
			'scope'                  => 'https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/gmail.readonly',
			'include_granted_scopes' => 'true',
			'state'                  => 1,
		);

		return http_build_query( $params );
	}

	/**
	 * Settings fields.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function settings_fields() {
		/**
		 * @var Oauth_Handler $oauth_handler
		 */
		$oauth_handler = Gravity_SMTP::container()->get( Connector_Service_Provider::GOOGLE_OAUTH_HANDLER );

		$oauth_handler->handle_response( $this->name );

		$token = $oauth_handler->get_access_token( $this->name );
		$has_token = $token && ! is_wp_error( $token );

		$settings = array(
			'title'       => esc_html__( 'Google / Gmail Settings', 'gravitysmtp' ),
			'hide_save'   => ! $has_token,
			'fields'      => array(),
		);

		if ( ! $has_token ) {
			$settings['fields'][] = array(
				'component' => 'Alert',
				'props'     => array(
					'customIconPrefix' => 'gravitysmtp-admin-icon',
					'theme'            => 'cosmos',
					'type'             => 'notice',
					'spacing'          => 3,
				),
				'fields'    => array(
					array(
						'component' => 'Text',
						'props' => array(
							'content'       => esc_html__( 'Before proceeding, make sure to save your settings with the Client ID and Client Secret.', 'gravitysmtp' ),
							'customClasses' => array( 'gform--display-block', 'gravitysmtp-integration__notice-message' ),
							'size'          => 'text-sm',
							'spacing'       => 4,
							'tagName'       => 'span',
						),
					),
					array(
						'component' => 'Link',
						'props'     => array(
							'content' => esc_html__( 'Read our Google / Gmail documentation', 'gravitysmtp' ),
							'customClasses' => array(
								'gform-link--theme-cosmos',
								'gravitysmtp-integration__notice-link',
								'gform-button',
								'gform-button--size-height-m',
								'gform-button--white',
								'gform-button--width-auto'
							),
							'href' => 'https://docs.gravitysmtp.com/category/integrations/google/',
							'target' => '_blank',
						),
					),
				),
			);
		}

		if ( isset( $_GET['code'] ) && ! $has_token ) {
			$settings['fields'][] = array(
				'component' => 'Alert',
				'props'     => array(
					'customIconPrefix' => 'gravitysmtp-admin-icon',
					'theme'            => 'cosmos',
					'type'             => 'error',
					'spacing'          => 3,
				),
				'fields'    => array(
					array(
						'component' => 'Text',
						'props'     => array(
							'content'       => esc_html__( 'Error Connecting to Google. Check your credentials and try again.', 'gravitysmtp' ),
							'weight'        => 'medium',
							'size'          => 'text-sm',
							'spacing'       => 2,
							'tagName'       => 'span',
						),
					),
				),
			);
		}

		$settings['fields'][] = array(
			'component' => 'Heading',
			'props'     => array(
				'content' => esc_html__( 'Configuration', 'gravitysmtp' ),
				'size'    => 'text-sm',
				'spacing' => [ 4, 0, 3, 0 ],
				'tagName' => 'h3',
				'type'    => 'boxed',
				'weight'  => 'medium',
			),
		);

		if ( ! $has_token ) {
			$settings['fields'][] = array(
				'component'     => 'LinkedHelpTextInput',
				'external'      => true,
				'handle_change' => true,
				'links'         => array(
					array(
						'key'   => 'link',
						'props' => array(
							'href'   => 'https://console.cloud.google.com/',
							'size'   => 'text-xs',
							'target' => '_blank',
						),
					),
				),
				'props'         => array(
					'helpTextAttributes' => array(
						'content' => esc_html__( 'To obtain a Client ID from Google / Gmail, log in to your {{link}}Google Cloud Console{{link}} and generate the Client ID.', 'gravitysmtp' ),
						'size'    => 'text-xs',
						'weight'  => 'regular',
					),
					'labelAttributes'    => array(
						'label'  => esc_html__( 'Client ID', 'gravitysmtp' ),
						'size'   => 'text-sm',
						'weight' => 'medium',
					),
					'name'               => self::SETTING_CLIENT_ID,
					'spacing'            => 6,
					'size'               => 'size-l',
					'value'              => $this->get_setting( self::SETTING_CLIENT_ID, '' ),
				),
			);

			$settings['fields'][] = array(
				'component'     => 'LinkedHelpTextInput',
				'external'      => true,
				'handle_change' => true,
				'links'         => array(
					array(
						'key'   => 'link',
						'props' => array(
							'href'   => 'https://console.cloud.google.com/',
							'size'   => 'text-xs',
							'target' => '_blank',
						),
					),
				),
				'props'         => array(
					'helpTextAttributes' => array(
						'content' => esc_html__( 'To obtain a Client Secret from Google / Gmail, log in to your {{link}}Google Cloud Console{{link}} and generate the Client ID.', 'gravitysmtp' ),
						'size'    => 'text-xs',
						'weight'  => 'regular',
					),
					'labelAttributes'    => array(
						'label'  => esc_html__( 'Client Secret', 'gravitysmtp' ),
						'size'   => 'text-sm',
						'weight' => 'medium',
					),
					'name'               => self::SETTING_CLIENT_SECRET,
					'spacing'            => 6,
					'size'               => 'size-l',
					'value'              => $this->get_setting( self::SETTING_CLIENT_SECRET, '' ),
				),
			);

			$settings['fields'][] = array(
				'component' => 'CopyInput',
				'external'  => true,
				'props'     => array(
					'actionButtonAttributes' => array(
						'customAttributes'     => array(
							'type' => 'button',
						),
						'icon'       => 'copy',
						'iconPrefix' => 'gravitysmtp-admin-icon',
						'label'      => esc_html__( 'Copy', 'gravitysmtp' ),
					),
					'labelAttributes'      => array(
						'label'  => esc_html__( 'Authorized redirect URI', 'gravitysmtp' ),
						'size'   => 'text-sm',
						'weight' => 'medium',
					),
					'name'                 => self::VALUE_REDIRECT_URI,
					'spacing'              => 6,
					'size'                 => 'size-l',
					'customAttributes'     => array(
						'readOnly' => true,
					),
					'value'                => urldecode( $oauth_handler->get_return_url( 'settings' ) ),
					'helpTextAttributes' => array(
						'content' => __( 'Copy this URL into the "Authorized redirect URIs" field of your Google web application.', 'gravitysmtp' ),
						'size'    => 'text-xs',
						'weight'  => 'regular',
					),
				),
			);

			$settings['fields'][] = array(
				'component' => 'Heading',
				'props'     => array(
					'content' => esc_html__( 'Authorization', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'spacing' => [ 4, 0, 3, 0 ],
					'tagName' => 'h3',
					'type'    => 'boxed',
					'weight'  => 'medium',
				),
			);


			$settings['fields'][] = array(
				'component' => 'BrandedButton',
				'external'  => true,
				'props'     => array(
					'label'   => esc_html__( 'Sign in with Google', 'gravitysmtp' ),
					'spacing' => 4,
					'Svg'     => 'GoogleAltLogo',
					'type'    => 'color',
				),
			);
		} else {
			$settings['fields'][] = array(
				'component' => 'Box',
				'props' => array(
					'customClasses' => array( 'gravitysmtp-google-integration__connected-message' ),
					'display'       => 'flex',
					'spacing'       => 3,
				),
				'fields'            => array(
					array(
						'component' => 'Icon',
						'props' => array(
							'customClasses' => array( 'gravitysmtp-google-integration__checkmark', 'gform-icon--preset-active', 'gform-icon-preset--status-correct', 'gform-alert__icon' ),
							'icon'          => 'checkmark-simple',
							'iconPrefix'    => 'gravitysmtp-admin-icon',
						),
					),
					array(
						'component' => 'Text',
						'props'     => array(
							'asHtml'  => true,
							'content' => sprintf(
								'%s <span class="gform-text gform-text--color-port gform-typography--size-text-sm gform-typography--weight-medium">%s</span>',
								esc_html__( 'Connected with email account:', 'gravitysmtp' ),
								esc_html( $oauth_handler->get_connection_details()['email'] )
							),
							'size'    => 'text-sm',
							'tagName' => 'span',
						),
					),
				),
			);

			$disconnect_url = admin_url( 'admin-post.php?action=smtp_disconnect_google' );

			$settings['fields'][] = array(
				'component' => 'Text',
				'props'     => array(
					'content' => sprintf( '<a href="%s" class="%s"><span class="gravitysmtp-admin-icon gravitysmtp-admin-icon--x-circle gform-button__icon"></span>%s</a>', $disconnect_url, 'gform-link gform-link--theme-cosmos gform-button gform-button--size-height-m gform-button--white gform-button--width-auto gform-button--active-type-loader gform-button--loader-after gform-button--icon-leading',  __( 'Disconnect from Google', 'gravitysmtp' ) ),
					'asHtml' => true,
					'spacing' => 6,
				),
			);

			$settings['fields'][] = array(
				'component' => 'Heading',
				'props'     => array(
					'content' => esc_html__( 'Configuration', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'spacing' => [ 4, 0, 4, 0 ],
					'tagName' => 'h3',
					'type'    => 'boxed',
					'weight'  => 'medium',
				),
			);

			$settings['fields'] = array_merge( $settings['fields'], $this->get_from_settings_fields() );
		}

		return $settings;
	}

	public function is_configured() {
		if ( $this->get_setting( 'access_token', false ) ) {
			/**
			 * @var Google_Oauth_Handler $oauth_handler
			 */
			$oauth_handler = Gravity_SMTP::container()->get( Connector_Service_Provider::GOOGLE_OAUTH_HANDLER );
			$token         = $oauth_handler->get_access_token();

			return $token;
		}

		return false;
	}

}
