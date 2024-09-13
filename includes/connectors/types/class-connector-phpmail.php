<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient;

/**
 * Connector for Generic/Custom SMTP integration.
 *
 * @since 1.0
 */
class Connector_Phpmail extends Connector_Base {

	const SETTING_USE_RETURN_PATH = 'use_return_path';

	protected $name        = 'phpmail';
	protected $title       = 'PHP Mail';
	protected $disabled    = false;
	protected $description = '';
	protected $logo        = 'PHP';
	protected $full_logo   = 'PHPFull';

	public function get_description() {
		return __( "Use your server's default PHP Mailer to send email.", 'gravitysmtp' );
	}

	/**
	 * Sending logic.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function send() {
		// noop - this connector just allows the default wp_mail to handle sends.
		return;
	}

	/**
	 * Get the request parameters for sending email through connector.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_request_params() {
		return array();
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
			self::SETTING_FROM_EMAIL       => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
			self::SETTING_FORCE_FROM_EMAIL => $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
			self::SETTING_FROM_NAME        => $this->get_setting( self::SETTING_FROM_NAME, '' ),
			self::SETTING_FORCE_FROM_NAME  => $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
			self::SETTING_USE_RETURN_PATH  => (bool) $this->get_setting( self::SETTING_USE_RETURN_PATH, false ),
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
			'title'       => esc_html__( 'PHP Mail Settings', 'gravitysmtp' ),
			'description' => '',
			'fields'      => array_merge(
				array(
					array(
						'component' => 'Alert',
						'props'     => array(
							'customIconPrefix' => 'gravitysmtp-admin-icon',
							'theme'            => 'cosmos',
							'type'             => 'info',
							'spacing'          => 5,
						),
						'fields' => array(
							array(
								'component' => 'Text',
								'props'     => array(
									'content' => esc_html__( 'When using PHP Mail, emails might not be delivered reliably.  For optimal performance, we recommend using a dedicated email provider.', 'gravitysmtp' ),
									'size'      => 'text-sm',
									'tagName' => 'span',
								),
							),
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
								'spacing' => [ 2, 0, 0, 0 ],
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
				$this->get_from_settings_fields()
			),
		);
	}

	/**
	 * Determine if the SMTP credentials are configured correctly.
	 *
	 * @since 1.0
	 *
	 * @return bool|\WP_Error
	 */
	public function is_configured() {
		global $phpmailer;
		return ! empty( $phpmailer );
	}

	public function update_wp_mail_froms( $atts ) {
		// This is how wp_mail() handles headers.
		if ( empty( $atts['headers'] ) ) {
			$atts['headers'] = array();
		} elseif ( ! is_array( $atts['headers'] ) ) {
			$atts['headers'] = explode( "\n", str_replace( "\r\n", "\n", $atts['headers'] ) );
		}

		$from        = $this->get_from( true );
		$force_name  = $this->get_setting( self::SETTING_FORCE_FROM_NAME, false );
		$force_email = $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false );
		$orig_froms  = empty( $atts['headers']['From'] ) ? array() : $this->get_email_from_header( 'From', $atts['headers']['From'] );
		$from_name   = '';
		$from_email  = '';

		if ( ! empty( $orig_froms ) ) {
			$from_name  = $orig_froms->recipients[0]->name();
			$from_email = $orig_froms->recipients[0]->email();
		}


		if ( ! empty( $from['name'] ) && ( empty( $from_name ) || $force_name ) ) {
			$from_name = $from['name'];
		}

		if ( empty( $from_email ) || $force_email ) {
			$from_email = $from['email'];
		}

		$recipient = new Recipient( $from_email, $from_name );

		if ( ! empty( $from_email ) ) {
			$atts['headers']['From'] = 'From: ' . $recipient->mailbox();
		}

		if ( isset( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'Content-type' ) === false ) {
			$atts['headers']['content-type'] = 'Content-type: ' . $atts['headers']['content-type'];
		}

		if ( $this->get_setting( self::SETTING_USE_RETURN_PATH, false ) ) {
			$atts['headers']['Return-Path'] = 'Return-Path: ' . $from_email;
		}

		return $atts;
	}

}
