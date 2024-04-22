<?php

namespace Gravity_Forms\Gravity_Tools\Utils;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Const_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Null_Logger;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Null_Logging_Provider;
use Gravity_Forms\Gravity_SMTP\Utils\Header_Parser;
use Gravity_Forms\Gravity_SMTP\Utils\Import_Data_Checker;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Parser;
use Gravity_Forms\Gravity_SMTP\Utils\Source_Parser;
use Gravity_Forms\Gravity_Tools\Cache\Cache;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;

class Utils_Service_Provider extends Service_Provider {

	const CACHE               = 'cache';
	const COMMON              = 'common';
	const HEADER_PARSER       = 'header_parser';
	const IMPORT_DATA_CHECKER = 'import_data_checker';
	const LOGGER              = 'logger_util';
	const RECIPIENT_PARSER    = 'recipient_parser';
	const SOURCE_PARSER       = 'source_parser';

	public function register( Service_Container $container ) {
		$container->add( Connector_Service_Provider::DATA_STORE_CONST, function () {
			return new Const_Data_Store();
		} );

		$container->add( Connector_Service_Provider::DATA_STORE_OPTS, function () {
			return new Opts_Data_Store();
		} );

		$container->add( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS, function () {
			return new Plugin_Opts_Data_Store();
		} );

		$container->add( Connector_Service_Provider::DATA_STORE_ROUTER, function () use ( $container ) {
			return new Data_Store_Router( $container->get( Connector_Service_Provider::DATA_STORE_CONST ), $container->get( Connector_Service_Provider::DATA_STORE_OPTS ), $container->get( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS ) );
		} );

		$container->add( self::COMMON, function () use ( $container ) {
			$data = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$key = $data->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_LICENSE_KEY, '' );
			return new Common( GRAVITY_MANAGER_URL, GRAVITY_SUPPORT_URL, $key );
		} );

		$container->add( self::CACHE, function () use ( $container ) {
			return new Cache( $container->get( self::COMMON ) );
		} );

		$container->add( self::HEADER_PARSER, function () {
			return new Header_Parser();
		} );

		$container->add( self::IMPORT_DATA_CHECKER, function () {
			return new Import_Data_Checker();
		} );

		$container->add( self::SOURCE_PARSER, function () {
			return new Source_Parser();
		} );

		$container->add( self::LOGGER, function() {
			return new Null_Logger( new Null_Logging_Provider() );
		} );

		$container->add( self::RECIPIENT_PARSER, function() {
			return new Recipient_Parser();
		} );
	}

}
