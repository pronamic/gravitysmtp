<?php

namespace Gravity_Forms\Gravity_SMTP\Routing;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Routing\Config\Routing_Config;
use Gravity_Forms\Gravity_SMTP\Routing\Config\Routing_Endpoints_Config;
use Gravity_Forms\Gravity_SMTP\Routing\Endpoints\Preview_Conditional_Routing;
use Gravity_Forms\Gravity_SMTP\Routing\Endpoints\Save_Routing_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Routing\Handlers\Conditional_Routing_Handler;
use Gravity_Forms\Gravity_SMTP\Routing\Handlers\Primary_Backup_Handler;
use Gravity_Forms\Gravity_SMTP\Utils\Source_Parser;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;

class Routing_Service_Provider extends Config_Service_Provider {

	const PRIMARY_BACKUP_HANDLER = 'primary_backup_handler';
	const ROUTING_CONFIG         = 'routing_config';
	const ROUTING_ENDPOINTS_CONFIG = 'routing_endpoints_config';
	const SAVE_ENDPOINT          = 'save_routing_settings_endpoint';
	const PREVIEW_ENDPOINT = 'preview_endpoint';
	const CONDITIONAL_ROUTING_HANDLER = 'conditional_routing_handler';

	const HOOK_PRIORITY_PRIMARY_BACKUP = 10;
	const HOOK_PRIORITY_CONDITIONAL_ROUTING = 11;

	protected $configs = array(
		self::ROUTING_CONFIG           => Routing_Config::class,
		self::ROUTING_ENDPOINTS_CONFIG => Routing_Endpoints_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$container->add( self::PRIMARY_BACKUP_HANDLER, function() use ( $container ) {
			return new Primary_Backup_Handler( $container->get( Connector_Service_Provider::DATA_STORE_ROUTER ), $container->get( Logging_Service_Provider::DEBUG_LOGGER ) );
		});

		$container->add( self::SAVE_ENDPOINT, function() use ( $container ) {
			return new Save_Routing_Settings_Endpoint( $container->get( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS ) );
		} );

		$container->add( self::CONDITIONAL_ROUTING_HANDLER, function() use ( $container ) {
			$data_store = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$debug_logger = $container->get( Logging_Service_Provider::DEBUG_LOGGER );

			return new Conditional_Routing_Handler( $data_store, $debug_logger );
		} );

		$container->add( self::PREVIEW_ENDPOINT, function() {
			return new Preview_Conditional_Routing();
		} );
	}

	public function init( Service_Container $container ) {
		add_filter( 'gravitysmtp_connector_for_sending', function( $current_connector, $email_args, $source = '' ) use ( $container ) {
			if ( $current_connector ) {
				return $current_connector;
			}
			return $container->get( self::PRIMARY_BACKUP_HANDLER )->handle( $current_connector, $email_args, $source );
		}, self::HOOK_PRIORITY_PRIMARY_BACKUP, 3 );

		add_filter( 'gravitysmtp_connector_for_sending', function ( $current_connector, $email_args ) use ( $container ) {
			$data_store   = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$conditionals = $data_store->get_plugin_setting( Save_Routing_Settings_Endpoint::PARAM_SETTINGS, array() );
			$handler      = $container->get( self::CONDITIONAL_ROUTING_HANDLER );
			$parser       = new Settings_to_Conditionals_Parser( new Source_Parser() );

			$email_data   = $parser->parse_email_data( $email_args );
			$conditionals = $parser->parse_settings( $conditionals, $email_data );

			$handler->set_conditionals( $conditionals );

			return $handler->handle( $current_connector, $email_args );
		}, self::HOOK_PRIORITY_CONDITIONAL_ROUTING, 2 );

		add_action( 'gravitysmtp_before_email_send', function() use ( $container ) {
			$container->get( self::PRIMARY_BACKUP_HANDLER )->reset();
			$container->get( self::CONDITIONAL_ROUTING_HANDLER )->reset();
		}, 0 );

		if ( Feature_Flag_Manager::is_enabled( 'smart_routing' ) ) {
			add_action( 'wp_ajax_' . Save_Routing_Settings_Endpoint::ACTION_NAME, function() use ( $container ) {
				$container->get( self::SAVE_ENDPOINT )->handle();
			}, 11 );
		}

		add_action( 'wp_ajax_' . Preview_Conditional_Routing::ACTION_NAME, function() use ( $container ) {
			$container->get( self::PREVIEW_ENDPOINT )->handle();
		}, 11 );
	}

}
