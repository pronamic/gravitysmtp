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

	const SETTING_ACCESS_TOKEN = 'access_token';

	protected $name        = 'google';
	protected $title       = 'Google / Gmail';
	protected $disabled    = true;
	protected $description = '';
	protected $logo        = 'Google';
	protected $full_logo   = 'GoogleFull';
	protected $google_svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" fill="none"><rect width="38" height="38" fill="#fff" rx="1"/><path fill="#4285F4" fill-rule="evenodd" d="M27.208 19.194a9.82 9.82 0 0 0-.155-1.749H19v3.308h4.602a3.933 3.933 0 0 1-1.707 2.58v2.146h2.764c1.616-1.489 2.549-3.68 2.549-6.285Z" clip-rule="evenodd"/><path fill="#34A853" fill-rule="evenodd" d="M19 27.55c2.308 0 4.244-.766 5.659-2.071l-2.764-2.146c-.765.513-1.745.816-2.895.816-2.227 0-4.112-1.504-4.784-3.524h-2.857v2.215A8.547 8.547 0 0 0 19 27.55Z" clip-rule="evenodd"/><path fill="#FBBC05" fill-rule="evenodd" d="M14.216 20.625A5.14 5.14 0 0 1 13.948 19c0-.564.097-1.111.268-1.625V15.16h-2.857A8.547 8.547 0 0 0 10.45 19c0 1.38.33 2.686.91 3.84l2.856-2.215Z" clip-rule="evenodd"/><path fill="#EA4335" fill-rule="evenodd" d="M19 13.85c1.255 0 2.382.432 3.268 1.28l2.453-2.453C23.24 11.297 21.305 10.45 19 10.45a8.547 8.547 0 0 0-7.64 4.71l2.856 2.215c.672-2.02 2.557-3.524 4.784-3.524Z" clip-rule="evenodd"/></svg>';

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
			$google_client = new Client();
			$google_client->setAccessToken( $this->get_setting( self::SETTING_ACCESS_TOKEN ) );

			$message = new Gmail\Message();
			$message->setRaw( $raw );

			$gmail_service = new Gmail( $google_client );
			$gmail_service->users_messages->send( 'me', $message );

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
			self::SETTING_ACCESS_TOKEN     => $this->get_setting( self::SETTING_ACCESS_TOKEN, '' ),
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
		/**
		 * @var Oauth_Handler $oauth_handler
		 */
		$oauth_handler = Gravity_SMTP::container()->get( Connector_Service_Provider::GOOGLE_OAUTH_HANDLER );

		$oauth_handler->handle_response( $this->name );

		$token = $oauth_handler->get_access_token( $this->name );

		$settings = array(
			'title'       => esc_html__( 'Google / Gmail Settings', 'gravitysmtp' ),
			'hide_save'   => ( ! $token || is_wp_error( $token ) ),
			'fields'      => array(),
		);

		if ( ! $token || is_wp_error( $token ) ) {
			$settings['fields'][] = array(
				'component' => 'Alert',
				'props'     => array(
					'theme' => 'cosmos',
					'type' => 'info',
					'spacing' => 3,
				),
				'fields' => array(
					array(
						'component' => 'Text',
						'props'     => array(
							'customClasses' => array( 'gravitysmtp-google-integration__alert-title' ),
							'content' => esc_html__( 'Gmail sending limits', 'gravitysmtp' ),
							'weight'    => 'medium',
							'size'      => 'text-sm',
							'spacing' => 2,
							'tagName' => 'span',
						),
					),
					array(
						'component' => 'Text',
						'props'     => array(
							'content' => esc_html__( 'Gmail is not recommended for sending high volumes of transactional emails.  If your site regularly sends lots of emails, you should consider using an email service provider that is designed for higher volumes.', 'gravitysmtp' ),
							'size'      => 'text-sm',
							'tagName' => 'span',
						),
					),
				),
			);
		}

		$settings['fields'][] = array(
			'component' => 'Heading',
			'props'     => array(
				'content' => esc_html__( 'Connection', 'gravitysmtp' ),
				'size'    => 'text-sm',
				'spacing' => [ 4, 0, 3, 0 ],
				'tagName' => 'h3',
				'type'    => 'boxed',
				'weight'  => 'medium',
			),
		);

		if ( ! $token || is_wp_error( $token ) ) {
			$settings['fields'][] = array(
				'component' => 'Text',
				'props'     => array(
					'content' => esc_html__( 'You are not currently connected. Click the button below to connect to your Gmail account.', 'gravitysmtp' ),
					'asHtml' => true,
					'spacing' => 3,
					'size' => 'text-sm',
				),
			);

			$settings['fields'][] = array(
				'component'   => 'Text',
				// The following 2 lines allow the setup wizard to use the appropriate link type
				'isOauthLink' => true,
				'wizardLink'  => sprintf( '<a href="%s" class="%s"><span class="%s">%s</span><span class="%s">%s</span></a>', $oauth_handler->get_oauth_url( 'wizard' ), 'gravitysmtp-google-integration__connect-button-link gform-link gform-link--theme-cosmos gform-button gform-button--height-l gform-button--primary-new', 'gravitysmtp-google-integration__google-icon', $this->google_svg, 'gravitysmtp-google-integration__connect-button-text', __( 'Connect to Google', 'gravitysmtp' ) ),
				'props'       => array(
					'content' => sprintf( '<a href="%s" class="%s"><span class="%s">%s</span><span class="%s">%s</span></a>', $oauth_handler->get_oauth_url(), 'gravitysmtp-google-integration__connect-button-link gform-link gform-link--theme-cosmos gform-button gform-button--height-l gform-button--primary-new', 'gravitysmtp-google-integration__google-icon', $this->google_svg, 'gravitysmtp-google-integration__connect-button-text', __( 'Connect to Google', 'gravitysmtp' ) ),
					'asHtml'  => true,
					'spacing' => 6,
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
		/**
		 * @var Google_Oauth_Handler $oauth_handler
		 */
		$oauth_handler = Gravity_SMTP::container()->get( Connector_Service_Provider::GOOGLE_OAUTH_HANDLER );
		$token         = $oauth_handler->get_access_token();

		return $token;
	}

}
