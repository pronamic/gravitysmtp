<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Endpoints;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Factory;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Save_Connector_Settings_Endpoint extends Endpoint {

	const PARAM_SETTINGS       = 'settings';
	const PARAM_CONNECTOR_TYPE = 'connector_type';
	const PARAM_NO_VALIDATE    = 'no_validate';

	const SETTING_ENABLED_CONNECTOR = 'enabled_connector';

	const ACTION_NAME = 'save_connector_settings';

	/**
	 * @var Connector_Factory $connector_factory
	 */
	protected $connector_factory;

	/**
	 * @var Opts_Data_Store;
	 */
	protected $data_store;

	/**
	 * @var Plugin_Opts_Data_Store
	 */
	protected $plugin_data_store;

	protected $required_params = array(
		self::PARAM_SETTINGS,
		self::PARAM_CONNECTOR_TYPE,
	);

	public function __construct( $data_store, $plugin_data_store, $connector_factory ) {
		$this->data_store        = $data_store;
		$this->plugin_data_store = $plugin_data_store;
		$this->connector_factory = $connector_factory;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$settings       = filter_input( INPUT_POST, self::PARAM_SETTINGS, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$type           = filter_input( INPUT_POST, self::PARAM_CONNECTOR_TYPE );
		$no_validate    = filter_has_var( INPUT_POST, self::PARAM_NO_VALIDATE );
		$type           = htmlspecialchars( $type );
		$configured_key = sprintf( 'gsmtp_connector_configured_%s', $type );

		$this->data_store->save_all( $settings, $type );
		delete_transient( $configured_key );

		if ( ! isset( $settings[ Connector_Base::SETTING_ENABLED ] ) ) {
			wp_send_json_success( $settings );
		}

		$connector_values = $this->plugin_data_store->get( self::SETTING_ENABLED_CONNECTOR, array() );
		$enabled          = $settings[ Connector_Base::SETTING_ENABLED ] == 'false' || ! (bool) $settings[ Connector_Base::SETTING_ENABLED ] ? false : true;

		if ( ! is_array( $connector_values ) ) {
			$connector_values = array();
		}

		$connector_values[ $type ] = $enabled;
		$this->plugin_data_store->save( self::SETTING_ENABLED_CONNECTOR, $connector_values );

		/**
		 * @var Connector_Base $connector
		 */
		$connector = $this->connector_factory->create( $type );
		$valid     = $no_validate ? true : $connector->is_configured();

		if ( is_wp_error( $valid ) ) {
			$error_message = $valid->get_error_message();
			Debug_Logger::log_message( sprintf(
				/* translators: %1$s is the connector, eg: SendGrid, %2$s is the error message */
				__( 'Error saving settings for %1$s: %2$s', 'gravitysmtp' ),
				$type,
				$error_message
			), 'error' );
			wp_send_json_error( $error_message, 500 );
		}

		wp_send_json_success( $settings );
	}

}
