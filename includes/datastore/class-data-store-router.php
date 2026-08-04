<?php

namespace Gravity_Forms\Gravity_SMTP\Data_Store;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Enums\Connector_Status_Enum;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;

class Data_Store_Router {

	protected $const_data_store;

	protected $opts_data_store;

	protected $plugin_opts_data_store;

	public function __construct( $const_data_store, $opts_data_store, $plugin_opts_data_store ) {
		$this->const_data_store       = $const_data_store;
		$this->opts_data_store        = $opts_data_store;
		$this->plugin_opts_data_store = $plugin_opts_data_store;
	}

	/**
	 * Helper method for retrieving plugin settings (i.e., settings for the plugin globally
	 * and not specific to this connector).
	 *
	 * @since 1.0
	 *
	 * @param $setting_name
	 * @param $default
	 *
	 * @return mixed|null
	 */
	public function get_plugin_setting( $setting_name, $default = null ) {
		if ( ! is_null( $this->const_data_store->get_plugin_const( $setting_name ) ) ) {
			return $this->const_data_store->get_plugin_const( $setting_name );
		}

		$val = $this->plugin_opts_data_store->get( $setting_name );

		if ( is_null( $val ) ) {
			return $default;
		}

		return $val;
	}

	/**
	 * Helper method to retrieve a saved setting specifically for this connector.
	 *
	 * @since 1.0
	 *
	 * @param $connector
	 * @param $setting_name
	 * @param $default
	 *
	 * @return mixed
	 */
	public function get_setting( $connector, $setting_name, $default = null ) {
		if ( ! is_null( $this->const_data_store->get( $setting_name, $connector ) ) ) {
			return $this->const_data_store->get( $setting_name, $connector );
		}

		$val = $this->opts_data_store->get( $setting_name, $connector );

		if ( is_null( $val ) ) {
			return $default;
		}

		return $val;
	}

	public function get_active_connector( $default = false ) {
		$connectors = $this->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, array() );
		$connectors = array_filter( $connectors );
		$connector  = empty( $connectors ) ? false : array_key_first( $connectors );

		if ( empty( $connector ) ) {
			return $default;
		}

		return $connector;
	}

	public function get_connector_status_of_type( $status_type, $default = false ) {
		$const_check = sprintf( 'GRAVITYSMTP_INTEGRATION_%s', strtoupper( $status_type ) );

		if ( defined( $const_check ) ) {
			return constant( $const_check );
		}

		$setting    = Connector_Status_Enum::setting_for_status( $status_type );
		$connectors = $this->get_plugin_setting( $setting, array() );
		$connectors = array_filter( (array) $connectors, function( $enabled ) {
			return ! empty( $enabled ) && $enabled !== false && $enabled !== 'false';
		} );

		if ( empty( $connectors ) ) {
			return $default;
		}

		// Primary/backup must be unique, but historical race conditions could persist
		// multiple flagged connectors. Surface it and prefer the connector whose own
		// settings agree — that is the one the integrations UI displays.
		if ( count( $connectors ) > 1 && $status_type !== Connector_Status_Enum::ENABLED ) {
			Debug_Logger::log_message( sprintf(
				/* translators: %1$s: status type (primary/backup), %2$s: connector list */
				__( 'Multiple connectors are flagged as the %1$s integration (%2$s); resolving to the one whose own settings agree.', 'gravitysmtp' ),
				$status_type,
				implode( ', ', array_keys( $connectors ) )
			), 'warning' );

			$connector_setting = $status_type === Connector_Status_Enum::PRIMARY ? Connector_Base::SETTING_IS_PRIMARY : Connector_Base::SETTING_IS_BACKUP;

			foreach ( array_keys( $connectors ) as $slug ) {
				$flag = $this->opts_data_store->get( $connector_setting, $slug );

				if ( ! empty( $flag ) && $flag !== 'false' ) {
					return $slug;
				}
			}
		}

		$connector = array_key_first( $connectors );

		return empty( $connector ) ? $default : $connector;
	}

}