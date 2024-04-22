<?php

namespace Gravity_Forms\Gravity_SMTP\Handler;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Factory;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Utils\Source_Parser;

class Mail_Handler {

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
		$opts_name  = 'gravitysmtp_config';
		$opts       = get_option( $opts_name, '{}' );
		$opts       = json_decode( $opts, true );
		$connectors = isset( $opts[ Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR ] ) ? $opts[ Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR ] : array();
		$configured = ! empty( array_filter( $connectors ) );

		return $configured;
	}

	public function mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		$debug     = debug_backtrace();
		$source    = $this->source_parser->get_source_from_trace( $debug );
		$type      = $this->data_store->get_active_connector( 'generic' );
		$connector = $this->get_connector( $type );

		$connector->init( $to, $subject, $message, $headers, $attachments, $source );
		$send = $connector->send();

		if ( $send === true ) {
			return true;
		}

		return false;
	}

}
