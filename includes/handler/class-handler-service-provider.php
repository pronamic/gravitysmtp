<?php

namespace Gravity_Forms\Gravity_SMTP\Handler;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Handler\Config\Handler_Endpoints_Config;
use Gravity_Forms\Gravity_SMTP\Handler\Endpoints\Resend_Email_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Handler_Service_Provider extends Config_Service_Provider {

	const HANDLER                  = 'mail_handler';
	const HANDLER_ENDPOINTS_CONFIG = 'handler_endpoints_config';

	const RESEND_EMAIL_ENDPOINT = 'resend_email_endpoint';

	protected $configs = array(
		self::HANDLER_ENDPOINTS_CONFIG => Handler_Endpoints_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$container->add( self::HANDLER, function () use ( $container ) {
			$factory       = $container->get( Connector_Service_Provider::CONNECTOR_FACTORY );
			$data_store    = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$source_parser = $container->get( Utils_Service_Provider::SOURCE_PARSER );

			return new Mail_Handler( $factory, $data_store, $source_parser );
		} );

		$container->add( self::RESEND_EMAIL_ENDPOINT, function () use ( $container ) {
			return new Resend_Email_Endpoint( $container->get( Connector_Service_Provider::EVENT_MODEL ), $container->get( Logging_Service_Provider::DEBUG_LOGGER ), $container->get( Utils_Service_Provider::ATTACHMENTS_SAVER ) );
		} );
	}

	public function init( Service_Container $container ) {
		add_action( 'wp_ajax_' . Resend_Email_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::RESEND_EMAIL_ENDPOINT )->handle();
		} );
	}

}