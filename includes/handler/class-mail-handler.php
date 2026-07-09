<?php

namespace Gravity_Forms\Gravity_SMTP\Handler;

use Gravity_Forms\Gravity_SMTP\Apps\Config\Email_Log_Config;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Factory;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Models\Suppressed_Emails_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Source_Parser;

class Mail_Handler {

	private static $configuration_status;

	/**
	 * @var Connector_Factory $connector_factory
	 */
	private $connector_factory;

	/**
	 * @var Data_Store_Router
	 */
	private $data_store;

	/**
	 * @var Source_Parser
	 */
	private $source_parser;

	/**
	 * @var Suppressed_Emails_Model
	 */
	private $suppressed_model;

	/**
	 * @var null A way to store the entry ID being acted upon.
	 */
	protected $entry_id = null;

	public function __construct( $connector_factory, $data_store, $source_parser, $suppressed_model ) {
		$this->connector_factory = $connector_factory;
		$this->data_store = $data_store;
		$this->source_parser = $source_parser;
		$this->suppressed_model = $suppressed_model;
	}

	public function set_entry_id( $entry_id ) {
		$this->entry_id = $entry_id;
	}

	public function get_entry_id() {
		return $this->entry_id;
	}

	private function get_connector( $type ) {
		return $this->connector_factory->create( $type );
	}

	public static function is_minimally_configured() {
		if ( ! is_null( self::$configuration_status ) ) {
			return self::$configuration_status;
		}

		if ( defined( 'GRAVITYSMTP_INTEGRATION_PRIMARY' ) ) {
			self::$configuration_status = true;
			return true;
		}

		$connectors = self::get_connectors_from_options( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR );

		$configured = ! empty( array_filter( $connectors, function( $enabled ) {
			return ! empty( $enabled ) && $enabled !== false && $enabled !== 'false';
		} ) );

		if ( $configured ) {
			self::$configuration_status = true;
			return true;
		}

		$connectors = self::get_connectors_from_options( Save_Connector_Settings_Endpoint::SETTING_BACKUP_CONNECTOR );

		$configured = ! empty( array_filter( $connectors, function( $enabled ) {
			return ! empty( $enabled ) && $enabled !== false && $enabled !== 'false';
		} ) );

		self::$configuration_status = $configured;

		return $configured;
	}

	public static function get_connectors_from_options( $type ) {
		$opts_name  = 'gravitysmtp_config';
		$opts       = get_option( $opts_name, '{}' );
		$opts       = json_decode( $opts, true );
		return isset( $opts[ $type ] ) ? $opts[ $type ] : array();
	}

	public static function is_test_mode() {
		$opts_name = 'gravitysmtp_config';
		$opts      = get_option( $opts_name, '{}' );
		$opts      = json_decode( $opts, true );
		$test_mode = isset( $opts[ Save_Plugin_Settings_Endpoint::PARAM_TEST_MODE ] ) ? $opts[ Save_Plugin_Settings_Endpoint::PARAM_TEST_MODE ] : null;

		return ! empty( $test_mode ) ? $test_mode !== 'false' : false;
	}

	public function mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		// Clear sources cache to ensure up-to-date info
		delete_transient( Email_Log_Config::SOURCE_LIST_ITEMS_TRANSIENT );

		// Re-send attempts put the source in the $headers array.
		if ( is_array( $headers ) && isset( $headers['source'] ) ) {
			$source = $headers['source'];
		} else {
			$debug  = debug_backtrace();
			$source = $this->source_parser->get_source_from_trace( $debug );
		}

		if ( ! empty( $attachments ) && ! is_array( $attachments ) ) {
			$attachments = array( $attachments );
		}

		/**
		 * Filters the wp_mail() arguments.
		 *
		 * @since 2.2.0
		 *
		 * @param array $args A compacted array of wp_mail() arguments, including the "to" email,
		 *                    subject, message, headers, and attachments values.
		 */
		$atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

		/**
		 * Filters whether to preempt sending an email.
		 *
		 * Returning a non-null value will short-circuit wp_mail(), returning
		 * that value instead. A boolean return value should be used to indicate whether
		 * the email was successfully sent.
		 *
		 * @since 1.9.5
		 *
		 * @param null|bool $return Short-circuit return value.
		 * @param array     $atts {
		 *     Array of the `wp_mail()` arguments.
		 *
		 *     @type string|string[] $to          Array or comma-separated list of email addresses to send message.
		 *     @type string          $subject     Email subject.
		 *     @type string          $message     Message contents.
		 *     @type string|string[] $headers     Additional headers.
		 *     @type string|string[] $attachments Paths to files to attach.
		 * }
		 */
		$pre_wp_mail = apply_filters( 'pre_wp_mail', null, $atts );
		if ( null !== $pre_wp_mail ) {
			return $pre_wp_mail;
		}

		/**
		 * Allows external code to modify which connector type is used for sending this email.
		 *
		 * Used primarily by the Backup Connection and Conditional Routing mechanisms.
		 *
		 * @since 1.2
		 * @since 2.2.1 Added the `$source` parameter.
		 *
		 * @param string|bool|array $current_type The connector type currently selected for sending. Defaults to `false`
		 *                                        when no connector has been chosen. May also be returned as an array in
		 *                                        the form `array( 'force' => true, 'connector' => '<type>' )` to force a
		 *                                        specific connector and skip the retry/backup logic.
		 * @param array             $email_data   An array of all the email data being used for this call, containing the
		 *                                        keys `to`, `subject`, `message`, `headers`, and `attachments`.
		 * @param string            $source       The source of the email (e.g. the plugin, theme, or WordPress core that
		 *                                        initiated the send).
		 *
		 * @return string|bool|array The connector type to use for sending, `false` to abort the send, or the force array
		 *                           described above.
		 */
		$type = apply_filters( 'gravitysmtp_connector_for_sending', false, array( 'to' => $to, 'subject' => $subject, 'message' => $message, 'headers' => $headers, 'attachments' => $attachments ), $source );
		$skip_retry = false;

		if ( is_array( $type ) && isset( $type['force'] ) ) {
			$skip_retry = true;
			$type = $type['connector'];
		}

		// Either no connector is defined, or the router has determined that this email shouldn't send.
		if ( $type === false ) {
			do_action( 'gravitysmtp_on_send_failure', 0 );
			return false;
		}

		$connector = $this->get_connector( $type );

		$connector->init( $to, $subject, $message, $headers, $attachments, $source );

		$filtered_recipients = array();

		if ( Feature_Flag_Manager::is_enabled( 'email_suppression' ) ) {
			$filtered_recipients = $connector->filter_suppressed_recipients( $this->suppressed_model );

			// All TO recipients suppressed — fully suppress the email.
			if ( $connector->get_att( 'to' )->count() === 0 ) {
				$suppressed_emails = array_column( $filtered_recipients, 'email' );
				$connector->handle_suppressed_email( implode( ', ', $suppressed_emails ), $source );
				return false;
			}
		}

		$send = $connector->send();

		if ( $send === true ) {
			// If any addresses were filtered, update status and log the reason.
			if ( ! empty( $filtered_recipients ) ) {
				$connector->handle_filtered_email( $filtered_recipients );
			}

			return true;
		}

		if ( $send !== true && $skip_retry ) {
			$failed_email_id = $send;
			do_action( 'gravitysmtp_on_send_failure', $failed_email_id );
			return false;
		}

		/**
		 * Allows third-parties to act when the primary connector has failed.
		 *
		 * @param int The ID of the failed email send.
		 *
		 * @since 2.2
		 *
		 * @return void
		 */
		do_action( 'gravitysmtp_on_primary_failure', $send );

		return $this->mail( $to, $subject, $message, $headers, $attachments );
	}

}
