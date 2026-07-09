<?php

namespace Gravity_Forms\Gravity_SMTP\Routing\Endpoints;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Routing\Handlers\Conditional_Routing_Handler;
use Gravity_Forms\Gravity_SMTP\Routing\Settings_to_Conditionals_Parser;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_SMTP\Utils\Source_Parser;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Preview_Conditional_Routing extends Endpoint {

	const ACTION_NAME = 'gravitysmtp_preview_conditional_routing';

	const PARAM_EMAIL_DATA = 'email_data';
	const PARAM_CONDITIONS = 'conditions';

	protected $minimum_cap = Roles::VIEW_ROUTING;

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( 'Missing required parameters.', 400 );
		}

		$email_data = filter_input( INPUT_POST, self::PARAM_EMAIL_DATA, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$settings   = filter_input( INPUT_POST, self::PARAM_CONDITIONS, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! is_array( $email_data ) ) {
			$email_data = array();
		}

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = Settings_to_Conditionals_Parser::recursively_fix_stringified_bools( $settings );

		$email_data = array_merge( array(
			'headers' => array(),
			'to' => '',
			'message' => '',
			'subject' => '',
			'attachments' => array(),
		), $email_data );

		$parser       = new Settings_to_Conditionals_Parser( new Source_Parser() );
		$email_data   = $parser->parse_email_data( $email_data );
		$conditionals = $parser->parse_settings( $settings, $email_data );

		$container = Gravity_SMTP::container();
		$handler   = new Conditional_Routing_Handler(
			$container->get( Connector_Service_Provider::DATA_STORE_ROUTER ),
			$container->get( Logging_Service_Provider::DEBUG_LOGGER )
		);
		$handler->set_conditionals( $conditionals );

		$connector = $handler->handle( 'no_match', $email_data );

		$response = array(
			'rule_selected' => $handler->get_selected_rule(),
			'connector'     => $connector,
		);

		wp_send_json_success( $response );
	}
}
