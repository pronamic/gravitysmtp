<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Endpoints;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Factory;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;

class Migrate_Settings_Endpoint extends Endpoint {

	const PARAM_CONNECTORS = 'connectors';
	const ACTION_NAME      = 'migrate_settings';

	/**
	 * @var Connector_Factory $connector_factory
	 */
	protected $connector_factory;

	/**
	 * @var Opts_Data_Store
	 */
	protected $data;

	protected $required_params = array(
		self::PARAM_CONNECTORS,
	);

	public function __construct( $connector_factory, $data_store ) {
		$this->connector_factory = $connector_factory;
		$this->data              = $data_store;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( 'Missing required parameters.', 400 );
		}

		$connectors = rgpost( self::PARAM_CONNECTORS );

		if ( ! is_array( $connectors ) ) {
			$connectors = array( $connectors );
		}

		$migrated_data = array();

		try {
			foreach ( $connectors as $connector_name ) {
				$migrated_data[ $connector_name ] = $this->migrate_connector_data( $connector_name );
			}

			wp_send_json_success( $migrated_data, 200 );
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	public function migrate_connector_data( $connector_name ) {
		$connector = $this->connector_factory->create( $connector_name );
		$map       = $connector->migration_map();

		if ( empty( $map ) ) {
			return array();
		}

		$settings_migrated = array();

		foreach ( $map as $migration_data ) {
			$original_key = rgar( $migration_data, 'original_key', '' );
			$new_key      = rgar( $migration_data, 'new_key', '' );
			$sub_key      = rgar( $migration_data, 'sub_key', false );
			$transform    = rgar( $migration_data, 'transform', false );

			if ( empty( $original_key ) || empty( $new_key ) ) {
				throw new \Exception( 'Invalid map configuration for ' . $connector_name );
			}

			$value = get_option( $original_key );

			if ( $sub_key && is_array( $value ) ) {
				$value = rgars( $value, $sub_key );
			}

			if ( empty( $value ) ) {
				continue;
			}

			if ( $transform ) {
				$value = call_user_func( $transform, $value );
			}

			$this->data->save( $new_key, $value, $connector_name );
			$settings_migrated[ $new_key ] = $value;
		}

		return $settings_migrated;
	}

}
