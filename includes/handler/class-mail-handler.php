<?php

namespace Gravity_Forms\Gravity_SMTP\Handler;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Factory;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
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

	public function __construct( $connector_factory, $data_store, $source_parser ) {
		$this->connector_factory = $connector_factory;
		$this->data_store = $data_store;
		$this->source_parser = $source_parser;
	}

	private function get_connector( $type ) {
		return $this->connector_factory->create( $type );
	}

	public static function is_minimally_configured() {
		if ( ! is_null( self::$configuration_status ) ) {
			return self::$configuration_status;
		}

		$connectors = self::get_connectors_from_options( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR );

		// We want to bypass our custom wp_mail method if phpmail is being used.
		if ( isset( $connectors['phpmail'] ) && $connectors['phpmail'] !== false && $connectors['phpmail'] != 'false' ) {
			self::$configuration_status = false;
			return false;
		}

		$configured = ! empty( array_filter( $connectors, function( $enabled ) {
			return ! empty( $enabled ) && $enabled !== false && $enabled !== 'false';
		} ) );

		if ( $configured ) {
			self::$configuration_status = true;
			return true;
		}

		$connectors = self::get_connectors_from_options( Save_Connector_Settings_Endpoint::SETTING_BACKUP_CONNECTOR );

		// We want to bypass our custom wp_mail method if phpmail is being used.
		if ( isset( $connectors['phpmail'] ) && $connectors['phpmail'] !== false && $connectors['phpmail'] != 'false' ) {
			self::$configuration_status = false;
			return false;
		}

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
		// Re-send attempts put the source in the $headers array.
		if ( is_array( $headers ) && isset( $headers['source'] ) ) {
			$source = $headers['source'];
		} else {
			$debug  = debug_backtrace();
			$source = $this->source_parser->get_source_from_trace( $debug );
		}


		/**
		 * Allows external code to modify which connector type is used for sending this email.
		 *
		 * Used primarily by the Backup Connection and Conditional Routing mechanisms.
		 *
		 * @since 1.2
		 *
		 * @param $current_type The current type being returned.
		 * @param $email_data   An array of all the email data being used for this call.
		 *
		 * @return string $type The connector type to use for sending.
		 */
		$type = apply_filters( 'gravitysmtp_connector_for_sending', false, array( 'to' => $to, 'subject' => $subject, 'message' => $message, 'headers' => $headers, 'attachments' => $attachments ) );

		// Either not connector is defined, or the router has determined that this email shouldn't send.
		if ( $type === false ) {
			return false;
		}

		$connector = $this->get_connector( $type );

		$connector->init( $to, $subject, $message, $headers, $attachments, $source );
		$send = $connector->send();

		if ( $send === true ) {
			return true;
		}

		return $this->mail( $to, $subject, $message, $headers, $attachments );
	}

}
