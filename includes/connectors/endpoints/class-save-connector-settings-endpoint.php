<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Endpoints;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Factory;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Save_Connector_Settings_Endpoint extends Endpoint {

	const PARAM_SETTINGS       = 'settings';
	const PARAM_CONNECTOR_TYPE = 'connector_type';
	const PARAM_NO_VALIDATE    = 'no_validate';

	const SETTING_ENABLED_CONNECTOR = 'enabled_connector';
	const SETTING_PRIMARY_CONNECTOR = 'primary_connector';
	const SETTING_BACKUP_CONNECTOR  = 'backup_connector';

	const ACTION_NAME = 'save_connector_settings';

	protected $minimum_cap = Roles::EDIT_INTEGRATIONS;

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

		// Snapshot state before writing so a failed credential validation below can
		// roll back instead of leaving a half-applied primary/backup transition.
		$previous_opts     = $this->data_store->get_opts( $type );
		$previous_statuses = array(
			self::SETTING_ENABLED_CONNECTOR => $this->plugin_data_store->get( self::SETTING_ENABLED_CONNECTOR ),
			self::SETTING_PRIMARY_CONNECTOR => $this->plugin_data_store->get( self::SETTING_PRIMARY_CONNECTOR ),
			self::SETTING_BACKUP_CONNECTOR  => $this->plugin_data_store->get( self::SETTING_BACKUP_CONNECTOR ),
		);

		$this->data_store->save_all( $settings, $type );
		delete_transient( $configured_key );

		// Only continue if this connector needs to be validated/enabled in some way.
		if (
			! isset( $settings[ Connector_Base::SETTING_ENABLED ] ) &&
			! isset( $settings[ Connector_Base::SETTING_IS_PRIMARY ] ) &&
			! isset( $settings[ Connector_Base::SETTING_IS_BACKUP ] )
		) {
			wp_send_json_success( $settings );
		}

		if ( isset( $settings[ Connector_Base::SETTING_ENABLED ] ) ) {
			$this->save_connector_status( $type, self::SETTING_ENABLED_CONNECTOR, $settings[ Connector_Base::SETTING_ENABLED ] );
		}

		if ( isset( $settings[ Connector_Base::SETTING_IS_PRIMARY ] ) ) {
			$this->save_connector_status( $type, self::SETTING_PRIMARY_CONNECTOR, $settings[ Connector_Base::SETTING_IS_PRIMARY ] );
		}

		if ( isset( $settings[ Connector_Base::SETTING_IS_BACKUP ] ) ) {
			$this->save_connector_status( $type, self::SETTING_BACKUP_CONNECTOR, $settings[ Connector_Base::SETTING_IS_BACKUP ] );
		}

		/**
		 * @var Connector_Base $connector
		 */
		$connector = $this->connector_factory->create( $type );
		$valid     = $no_validate ? true : $connector->is_configured();

		if ( is_wp_error( $valid ) ) {
			// The client treats this response as "nothing saved" — make that true.
			$this->data_store->delete_all( $type );
			$this->data_store->save_all( $previous_opts, $type );

			foreach ( $previous_statuses as $status_type => $previous_value ) {
				// A map that did not exist before restores as empty, not skipped —
				// otherwise a failed first-ever save leaves the map it created behind.
				$this->plugin_data_store->save( $status_type, is_null( $previous_value ) ? array() : $previous_value );
			}

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

	/**
	 * Save a connector's status (enabled, primary, backup) in the settings array.
	 *
	 * Primary and backup are exclusive: granting one to a connector clears the flag
	 * from every other connector in the same write — in the status map and in each
	 * demoted connector's own settings (which the integrations UI reads). Uniqueness
	 * was previously the client's job via separate parallel unset requests, which
	 * raced this read-modify-write and could persist multiple primaries.
	 *
	 * @param $type
	 * @param $status_type
	 * @param $enabled
	 *
	 * @return void
	 */
	protected function save_connector_status( $type, $status_type, $enabled ) {
		$connector_values = $this->plugin_data_store->get( $status_type, array() );

		if ( ! is_array( $connector_values ) ) {
			$connector_values = array();
		}

		$exclusive_settings = array(
			self::SETTING_PRIMARY_CONNECTOR => Connector_Base::SETTING_IS_PRIMARY,
			self::SETTING_BACKUP_CONNECTOR  => Connector_Base::SETTING_IS_BACKUP,
		);

		if ( isset( $exclusive_settings[ $status_type ] ) && Booliesh::get( $enabled ) ) {
			foreach ( array_keys( $connector_values ) as $slug ) {
				if ( $slug === $type ) {
					continue;
				}

				if ( Booliesh::get( $connector_values[ $slug ] ) ) {
					$this->data_store->save( $exclusive_settings[ $status_type ], false, $slug );
				}

				$connector_values[ $slug ] = false;
			}
		}

		// Normalize to a real boolean so the maps hold one type; readers no longer
		// depend on catching the string 'false'.
		$connector_values[ $type ] = Booliesh::get( $enabled );
		$this->plugin_data_store->save( $status_type, $connector_values );
	}

}
