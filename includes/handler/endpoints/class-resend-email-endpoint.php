<?php

namespace Gravity_Forms\Gravity_SMTP\Handler\Endpoints;

use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_SMTP\Utils\Attachments_Saver;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Collection;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Parser;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Resend_Email_Endpoint extends Endpoint {

	const ACTION_NAME = 'gravitysmtp_resend_email';

	const PARAM_EMAIL_ID = 'email_id';
	// Optional override for recipient address(es) when resending
	const PARAM_RECIPIENT = 'recipient';
	const PARAM_CC        = 'cc';
	const PARAM_BCC       = 'bcc';

	protected $minimum_cap = Roles::EDIT_EMAIL_LOG_DETAILS;

	/**
	 * @var Event_Model
	 */
	protected $events;

	/**
	 * @var Debug_Logger
	 */
	protected $logger;

	/**
	 * @var Attachments_Saver
	 */
	protected $attachments_handler;

	protected $required_params = array(
		self::PARAM_EMAIL_ID,
	);

	public function __construct( $event_model, $logger, $attachments_handler ) {
		$this->events = $event_model;
		$this->logger = $logger;
		$this->attachments_handler = $attachments_handler;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$email_id = filter_input( INPUT_POST, self::PARAM_EMAIL_ID, FILTER_SANITIZE_NUMBER_INT );
		$email    = $this->events->get( $email_id );
		$extra    = unserialize( $email['extra'], array( 'allowed_classes' => array( Recipient_Collection::class, Recipient::class ) ) );

		if ( ! is_array( $extra ) ) {
			wp_send_json_error( __( 'Could not read stored email data.', 'gravitysmtp' ) );
		}

		$headers  = $extra['headers'];
		// Use SANITIZE_FULL_SPECIAL_CHARS for cc/bcc (plain email lists).
		// Use sanitize_text_field for recipient since it may contain mailbox format Name <email>.
		$cc_raw        = filter_input( INPUT_POST, self::PARAM_CC, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$bcc_raw       = filter_input( INPUT_POST, self::PARAM_BCC, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$recipient_raw = isset( $_POST[ self::PARAM_RECIPIENT ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::PARAM_RECIPIENT ] ) ) : null;
		$to = $extra['to']; // default to original recipient
		if ( null !== $recipient_raw ) {
			$collection = $this->parse_recipients( $recipient_raw );
			if ( is_wp_error( $collection ) ) {
				wp_send_json_error( $collection->get_error_message() );
			}
			$to = $collection->as_string();
		}

		// Sanity check to ensure we don't try resending an un-sendable email.
		if ( ! $email['can_resend'] ) {
			// @translators: %s represents the email ID as a numeric string.
			$error_message = __( 'Attempted to resend email %s, but could not due to either the message body not being stored, or attachments not being saved.', 'gravitysmtp' );
			$this->logger->log_warning( sprintf( $error_message, $email_id ) );
			wp_send_json_error( __( 'Email could not be resent as it was not stored with all required values.', 'gravitysmtp' ) );
		}

		if ( ! empty( $extra['attachments'] ) ) {
			foreach( $extra['attachments'] as $key => $og_path ) {
				$new_path = $this->attachments_handler->get_saved_attachment( $email_id, $og_path );
				$extra['attachments'][ $key ] = $new_path;
			}
		}

		$cc_str = null !== $cc_raw ? trim( $cc_raw ) : '';
		if ( $cc_str !== '' ) {
			$cc_collection = $this->parse_recipients( $cc_str );
			if ( is_wp_error( $cc_collection ) ) {
				wp_send_json_error( $cc_collection->get_error_message() );
			}
			$headers['cc'] = $cc_collection->as_string();
		} else {
			unset( $headers['cc'] );
		}

		$bcc_str = null !== $bcc_raw ? trim( $bcc_raw ) : '';
		if ( $bcc_str !== '' ) {
			$bcc_collection = $this->parse_recipients( $bcc_str );
			if ( is_wp_error( $bcc_collection ) ) {
				wp_send_json_error( $bcc_collection->get_error_message() );
			}
			$headers['bcc'] = $bcc_collection->as_string();
		} else {
			unset( $headers['bcc'] );
		}

		$headers['source'] = $extra['source'];

		foreach ( $headers as $idx => $header ) {
			if ( is_a( $header, Recipient_Collection::class ) ) {
				$emails       = $header->recipients();
				$emails_array = array();

				foreach ( $emails as $email_recipient ) {
					$emails_array[] = $email_recipient->email();
				}

				$headers[ $idx ] = implode( ', ', $emails_array );
			}
		}

		$success = wp_mail( $to, $email['subject'], $email['message'], $headers, $extra['attachments'] );

		if ( ! $success ) {
			wp_send_json_error( __( 'Email could not be resent; check your logs for more details.', 'gravitysmtp' ) );
		}

		wp_send_json_success( $success );
	}

	/**
	 * Parse a raw email string into a validated Recipient_Collection.
	 *
	 * @since 2.2.0
	 *
	 * @param string $raw_value The raw comma-separated email string.
	 *
	 * @return Recipient_Collection|\WP_Error Collection of recipients, or WP_Error if empty/invalid.
	 */
	private function parse_recipients( $raw_value ) {
		$parser     = new Recipient_Parser();
		$collection = $parser->parse( $raw_value );

		if ( $collection->count() === 0 ) {
			return new \WP_Error( 'empty_email_list', __( 'Email address list is empty.', 'gravitysmtp' ) );
		}

		// Validate each parsed email address.
		foreach ( $collection->recipients() as $recipient ) {
			if ( ! is_email( $recipient->email() ) ) {
				return new \WP_Error(
					'invalid_email',
					// translators: %s is the invalid email address.
					sprintf( __( 'Invalid email address: %s', 'gravitysmtp' ), $recipient->email() )
				);
			}
		}

		return $collection;
	}

}
