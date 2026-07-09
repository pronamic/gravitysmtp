<?php

namespace Gravity_Forms\Gravity_SMTP\Routing\Config;

use Gravity_Forms\Gravity_SMTP\Routing\Endpoints\Preview_Conditional_Routing;
use Gravity_Forms\Gravity_SMTP\Routing\Endpoints\Save_Routing_Settings_Endpoint;
use Gravity_Forms\Gravity_Tools\Config;

class Routing_Endpoints_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		return is_admin();
	}

	public function data() {
		return array(
			'common' => array(
				'endpoints' => array(
					Save_Routing_Settings_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Save_Routing_Settings_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Save_Routing_Settings_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					Preview_Conditional_Routing::ACTION_NAME => array(
						'action' => array(
							'value'   => Preview_Conditional_Routing::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Preview_Conditional_Routing::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
				),
			),
		);
	}

}
