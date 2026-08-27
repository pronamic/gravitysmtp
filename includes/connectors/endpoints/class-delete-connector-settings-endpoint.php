<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Endpoints;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Factory;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Delete_Connector_Settings_Endpoint extends Endpoint {

	const PARAM_CONNECTOR_TYPE = 'connector_type';

	const ACTION_NAME = 'delete_connector_settings';

	protected $minimum_cap = Roles::EDIT_INTEGRATIONS;

	/**
	 * @var Opts_Data_Store
	 */
	protected $data_store;

	/**
	 * @var Plugin_Opts_Data_Store
	 */
	protected $plugin_data_store;

	/**
	 * @var Connector_Factory
	 */
	protected $connector_factory;

	protected $required_params = array(
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
		if ( ! current_user_can( $this->minimum_cap ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'gravitysmtp' ), 403 );
		}

		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$type   = filter_input( INPUT_POST, self::PARAM_CONNECTOR_TYPE );
		$type   = sanitize_key( $type );
		$result = $this->delete_connector_settings( $type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message(), 400 );
		}

		wp_send_json_success();
	}

	/**
	 * Delete all locally stored settings and statuses for a connector.
	 *
	 * @param string $type Connector type.
	 *
	 * @return true|\WP_Error True on success, or an error for an unknown connector.
	 */
	protected function delete_connector_settings( $type ) {
		try {
			$this->connector_factory->create( $type );
		} catch ( \InvalidArgumentException $e ) {
			return new \WP_Error( 'invalid_connector_type', __( 'Invalid connector type.', 'gravitysmtp' ) );
		}

		$status_settings = array(
			Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR,
			Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR,
			Save_Connector_Settings_Endpoint::SETTING_BACKUP_CONNECTOR,
		);

		// Clear the status flags before deleting the stored settings so an
		// interrupted request leaves a configured-but-unflagged connector
		// rather than routing mail to a connector with no credentials.
		foreach ( $status_settings as $status_setting ) {
			$connector_values = $this->plugin_data_store->get( $status_setting, 'config', array() );

			if ( ! is_array( $connector_values ) ) {
				$connector_values = array();
			}

			$connector_values[ $type ] = false;
			$this->plugin_data_store->save( $status_setting, $connector_values );
		}

		$this->data_store->delete_all( $type );
		delete_transient( sprintf( 'gsmtp_connector_configured_%s', $type ) );

		return true;
	}
}
