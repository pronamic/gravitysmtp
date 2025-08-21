<?php

namespace Gravity_Forms\Gravity_SMTP\Notifications\Config;

use Gravity_Forms\Gravity_SMTP\Notifications\Email_Summary_Handler;
use Gravity_Forms\Gravity_Tools\Config;

class Notifications_Endpoints_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		return is_admin();
	}

	public function data() {
		return array(
			'common' => array(
				'endpoints' => array(
					Email_Summary_Handler::ACTION_NAME => array(
						'action' => array(
							'value'   => Email_Summary_Handler::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Email_Summary_Handler::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
				),
			),
		);
	}
}
