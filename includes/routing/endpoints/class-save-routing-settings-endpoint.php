<?php

namespace Gravity_Forms\Gravity_SMTP\Routing\Endpoints;

use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Routing\Settings_to_Conditionals_Parser;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Save_Routing_Settings_Endpoint extends Endpoint {

	const ACTION_NAME = 'save_routing_settings';

	const PARAM_SETTINGS = 'routing_settings';

	protected $minimum_cap = Roles::EDIT_ROUTING;

	/**
	 * @var Plugin_Opts_Data_Store
	 */
	protected $plugin_data_store;

	public function __construct( $plugin_data_store ) {
		$this->plugin_data_store = $plugin_data_store;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$settings = filter_input( INPUT_POST, self::PARAM_SETTINGS, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		// An empty recipe list (all recipes deleted) sends no array, so default to an empty list.
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = Settings_to_Conditionals_Parser::recursively_fix_stringified_bools( $settings );

		$this->plugin_data_store->save( self::PARAM_SETTINGS, $settings );

		wp_send_json_success( $settings );
	}

}
