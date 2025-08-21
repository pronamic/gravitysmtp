<?php

namespace Gravity_Forms\Gravity_SMTP\Notifications;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Notifications\Config\Notifications_Endpoints_Config;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;

class Notifications_Service_Provider extends Config_Service_Provider {

	const EMAIL_SUMMARY_HANDLER         = 'email_summary_handler';
	const NOTIFICATIONS_ENDPOINT_CONFIG = 'notifications_endpoint_config';

	protected $configs = array(
		self::NOTIFICATIONS_ENDPOINT_CONFIG => Notifications_Endpoints_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$this->container->add( self::EMAIL_SUMMARY_HANDLER, function () use ( $container ) {
			return new Email_Summary_Handler( $container->get( Connector_Service_Provider::DATA_STORE_ROUTER ), $container->get( Connector_Service_Provider::EVENT_MODEL ) );
		} );
	}

	public function init( Service_Container $container ) {
		add_action( 'gravitysmtp_cron_' . Email_Summary_Handler::ACTION_NAME, function () use ( $container ) {
			$container->get( self::EMAIL_SUMMARY_HANDLER )->handle();
		} );

		add_action( 'wp_ajax_' . Email_Summary_Handler::ACTION_NAME, function () use ( $container ) {
			wp_verify_nonce( 'security', Email_Summary_Handler::ACTION_NAME );
			try {
				$container->get( self::EMAIL_SUMMARY_HANDLER )->handle( false );
			} catch( \Exception $e ) {
				wp_send_json_error( $e->getMessage(), 500 );
			}

			wp_send_json_success( 'Digest sent.' );
		} );

		if ( ! wp_next_scheduled( 'gravitysmtp_cron_' . Email_Summary_Handler::ACTION_NAME ) ) {
			wp_schedule_event( time(), 'hourly', 'gravitysmtp_cron_' . Email_Summary_Handler::ACTION_NAME );
		}
	}
}
