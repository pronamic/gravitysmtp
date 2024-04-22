<?php

namespace Gravity_Forms\Gravity_SMTP\Handler;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Handler_Service_Provider extends Service_Provider {

	const HANDLER = 'mail_handler';

	public function register( \Gravity_Forms\Gravity_Tools\Service_Container $container ) {
		$container->add( self::HANDLER, function () use ( $container ) {
			$factory       = $container->get( Connector_Service_Provider::CONNECTOR_FACTORY );
			$data_store    = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$source_parser = $container->get( Utils_Service_Provider::SOURCE_PARSER );

			return new Mail_Handler( $factory, $data_store, $source_parser );
		} );
	}

}