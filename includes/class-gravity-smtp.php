<?php

namespace Gravity_Forms\Gravity_SMTP;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Apps\Setup_Wizard\Setup_Wizard_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Assets\Assets_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Const_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Environment\Environment_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Handler\Handler_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Migration\Migration_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Pages\Page_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Routing\Routing_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Telemetry\Telemetry_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Translations\Translations_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Users\Users_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Providers\Config_Collection_Service_Provider;
use Gravity_Forms\Gravity_Tools\Updates\Updates_Service_Provider;
use Gravity_Forms\Gravity_Tools\Upgrades\Upgrade_Routines;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

/**
 * Loads Gravity SMTP.
 *
 * @since 1.0
 */
class Gravity_SMTP {

	/**
	 * @var Service_Container $container
	 */
	public static $container;

	/**
	 * Loads the required files.
	 *
	 * @since  1.0
	 */
	public static function load_plugin() {
		self::clear_cache_for_oauth();

		self::load_providers();
	}

	/**
	 * Run upgrade routines on plugins_loaded to ensure users have the most-up-to-date system when updating.
	 *
	 * @return void
	 */
	public static function run_upgrade_routines() {
		// Allow upgrades to be skipped if needed.
		if ( defined( 'GRAVITYSMTP_SKIP_UPGRADE_CHECK' ) && GRAVITYSMTP_SKIP_UPGRADE_CHECK ) {
			return;
		}

		$routines = new Upgrade_Routines( 'gravitysmtp' );

		// Ensure tables are set up properly
		$routines->add( 'emails_tables', array( self::class, 'create_emails_tables' ) );

		// Ensure a primary connection exists
		$routines->add( 'primary_connection', array( self::class, 'set_primary_connection' ) );

		add_action( 'plugins_loaded', function() use ( $routines ) {
			$routines->handle();
		}, 10 );
	}

	private static function clear_cache_for_oauth() {
		$payload = filter_input( INPUT_POST, 'auth_payload' );

		if ( ! empty( $payload ) ) {
			$configured_key = sprintf( 'gsmtp_connector_configured_%s', 'google' );
			delete_transient( $configured_key );
		}
	}

	public static function set_primary_connection() {
		$const  = new Const_Data_Store();
		$opts   = new Opts_Data_Store();
		$plugin = new Plugin_Opts_Data_Store();
		$router = new Data_Store_Router( $const, $opts, $plugin );

		$primaries = $router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, array() );
		$selected  = array_filter( $primaries );

		if ( ! empty( $selected ) ) {
			return;
		}

		$enabled  = $router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, array() );
		$selected = array_filter( $enabled );

		if ( empty( $selected ) ) {
			return;
		}

		$keys                            = array_keys( $selected );
		$enabled_connector               = reset( $keys );
		$primaries[ $enabled_connector ] = true;
		$opts->save( Connector_Base::SETTING_IS_PRIMARY, true, $enabled_connector );

		$plugin->save( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, $primaries );
	}

	public static function create_emails_tables() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$table_name = $wpdb->prefix . 'gravitysmtp_events';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "
			CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
			    date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    status varchar(100) NOT NULL,
			    service varchar(100) NOT NULL,
			    subject varchar(100) NOT NULL,
			    message text NOT NULL,
			    extra mediumtext NOT NULL,
			    PRIMARY KEY (id)
		    ) $charset_collate;
		";

		dbDelta( $sql );

		$log_table_name = $wpdb->prefix . 'gravitysmtp_event_logs';

		$sql = "
			CREATE TABLE $log_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
                event_id mediumint(9) NOT NULL,
			    action_name varchar(100) NOT NULL,
			    log_value text NOT NULL,
			    date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    PRIMARY KEY (id)
		    ) $charset_collate;
		";

		dbDelta( $sql );

		$debug_log_table_name = $wpdb->prefix . 'gravitysmtp_debug_log';

		$sql = "
			CREATE TABLE $debug_log_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
			    priority varchar(100) NOT NULL,
			    line text NOT NULL,
			    date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    PRIMARY KEY (id)
		    ) $charset_collate;
		";

		dbDelta( $sql );
	}

	public static function container() {
		if ( is_null( self::$container ) ) {
			self::load_providers();
		}

		return self::$container;
	}

	protected static function load_providers() {
		$full_path = __FILE__;
		self::$container = new Service_Container();

		// Common Providers
		self::$container->add_provider( new Users_Service_Provider() );
		self::$container->add_provider( new Utils_Service_Provider() );
		self::$container->add_provider( new Updates_Service_Provider( $full_path ) );
		self::$container->add_provider( new Translations_Service_Provider() );
		self::$container->add_provider( new Config_Collection_Service_Provider( 'gravitysmtp/v1' ) );
		self::$container->add_provider( new Connector_Service_Provider() );
		self::$container->add_provider( new Assets_Service_Provider( self::get_base_url(), self::get_local_dev_base_url(), self::get_base_dir() ) );
		self::$container->add_provider( new App_Service_Provider( self::get_base_url() ) );
		self::$container->add_provider( new Logging_Service_Provider() );
		self::$container->add_provider( new Handler_Service_Provider() );
		self::$container->add_provider( new Page_Service_Provider( self::get_base_url() ) );
		self::$container->add_provider( new Setup_Wizard_Service_Provider() );
		self::$container->add_provider( new Telemetry_Service_Provider() );
		self::$container->add_provider( new Environment_Service_Provider() );
		self::$container->add_provider( new Routing_Service_Provider() );
		self::$container->add_provider( new Migration_Service_Provider() );
	}

	public static function get_base_url() {
		return plugins_url( '', dirname( __FILE__ ) );
	}

	public static function get_base_dir() {
		return plugin_dir_path( dirname( __FILE__ ) );
	}

	public static function get_local_dev_base_url() {
		$url = self::get_base_url();

		if ( ! defined( 'GRAVITYSMTP_ENABLE_HMR' ) || ! GRAVITYSMTP_ENABLE_HMR ) {
			return $url . '/assets/js/dist';
		}

		$config = dirname( dirname( __FILE__ ) ) . '/local-config.json';

		if ( ! file_exists( $config ) ) {
			return $url . '/assets/js/dist';
		}

		// Get port info from local-config.json
		$json = file_get_contents( $config );
		$data = json_decode( $json, true );
		$port = isset( $data['hmr_port'] ) ? $data['hmr_port'] : '9003';

		// Set up the base URL and path.
		$base   = parse_url( $url, PHP_URL_HOST );
		$scheme = parse_url( $url, PHP_URL_SCHEME );

		return sprintf( '%s://%s:%s', $scheme, $base, $port );
	}

	public static function activation_hook() {
		self::load_providers();
		self::create_emails_tables();
		do_action( 'gravitysmtp_post_activation' );
	}

}
