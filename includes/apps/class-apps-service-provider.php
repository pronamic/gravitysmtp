<?php

namespace Gravity_Forms\Gravity_SMTP\Apps;

use Gravity_Forms\Gravity_SMTP\Apps\Config\Apps_Config;
use Gravity_Forms\Gravity_SMTP\Apps\Config\Email_Log_Config;
use Gravity_Forms\Gravity_SMTP\Apps\Config\Email_Log_Single_Config;
use Gravity_Forms\Gravity_SMTP\Apps\Config\Settings_Config;
use Gravity_Forms\Gravity_SMTP\Apps\Config\Tools_Config;
use Gravity_Forms\Gravity_SMTP\Assets\Assets_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_Tools\Apps\Registers_Apps;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;

class App_Service_Provider extends Config_Service_Provider {

	use Registers_Apps;

	const APPS_CONFIG             = 'apps_config';
	const EMAIL_LOG_CONFIG        = 'email_log_config';
	const EMAIL_LOG_SINGLE_CONFIG = 'email_log_single_config';
	const SETTINGS_CONFIG         = 'settings_config';
	const TOOLS_CONFIG            = 'tools_config';

	const SHOULD_ENQUEUE_SETUP_WIZARD = 'should_enqueue_setup_wizard';

	protected $configs = array(
		self::APPS_CONFIG             => Apps_Config::class,
		self::EMAIL_LOG_CONFIG        => Email_Log_Config::class,
		self::EMAIL_LOG_SINGLE_CONFIG => Email_Log_Single_Config::class,
		self::SETTINGS_CONFIG         => Settings_Config::class,
		self::TOOLS_CONFIG            => Tools_Config::class,
	);

	protected $plugin_url;

	public function __construct( $plugin_url ) {
		$this->plugin_url = $plugin_url;
	}

	public function register( Service_Container $container ) {
		parent::register( $container );

		$this->container->add( self::SHOULD_ENQUEUE_SETUP_WIZARD, function() use ( $container ) {
			return function() {
				$page = filter_input( INPUT_GET, 'page' );

				if ( ! is_string( $page ) ) {
					return false;
				}

				$page = htmlspecialchars( $page );

				$container      = Gravity_SMTP::container();
				$should_display = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER )->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_SETUP_WIZARD_SHOULD_DISPLAY, 'true' ) === 'true';

				return $should_display ? strpos( $page, 'gravitysmtp-' ) !== false : $page === 'gravitysmtp-settings';
			};
		}, true );

		$min = $this->container->get( Assets_Service_Provider::ENVIRONMENT_DETAILS )->get_min();
		$ver = $this->container->get( Assets_Service_Provider::ENVIRONMENT_DETAILS )->get_version();

		$this->register_setup_wizard_app( $min, $ver );
		$this->register_settings_app( $min, $ver );
		$this->register_activity_log_app( $min, $ver );
		$this->register_tools_app( $min, $ver );
	}

	protected function register_setup_wizard_app( $min, $ver ) {
		$args = array(
			'app_name'     => 'setup-wizard',
			'script_name'  => 'gravitysmtp_scripts_admin',
			'object_name'  => 'gravitysmtp_admin_config',
			'chunk'        => './setup-wizard',
			'enqueue'      => $this->container->get( self::SHOULD_ENQUEUE_SETUP_WIZARD ),
			'css'          => array(
				'handle' => 'setup_wizard_styles',
				'src'    => $this->plugin_url . "/assets/css/dist/setup-wizard{$min}.css",
				'deps'   => array( 'gravitysmtp_styles_base' ),
				'ver'    => $ver,
			),
			'root_element' => 'gravitysmtp-setup-wizard-root',
		);

		$this->register_app( $args );
	}

	protected function register_activity_log_app( $min, $ver ) {
		$args = array(
			'app_name'     => 'activity-log',
			'script_name'  => 'gravitysmtp_scripts_admin',
			'object_name'  => 'gravitysmtp_admin_config',
			'chunk'        => './activity-log',
			'enqueue'      => array( $this, 'should_enqueue_activity_log' ),
			'css'          => array(
				'handle' => 'activity_log_styles',
				'src'    => $this->plugin_url . "/assets/css/dist/activity-log{$min}.css",
				'deps'   => array( 'gravitysmtp_styles_base' ),
				'ver'    => $ver,
			),
			'root_element' => 'gravitysmtp-activity-log-app-root',
		);

		$this->register_app( $args );
	}

	protected function register_settings_app( $min, $ver ) {
		$args = array(
			'app_name'     => 'settings',
			'script_name'  => 'gravitysmtp_scripts_admin',
			'object_name'  => 'gravitysmtp_admin_config',
			'chunk'        => './settings',
			'enqueue'      => array( $this, 'should_enqueue_settings' ),
			'css'          => array(
				'handle' => 'settings_styles',
				'src'    => $this->plugin_url . "/assets/css/dist/settings{$min}.css",
				'deps'   => array( 'gravitysmtp_styles_base' ),
				'ver'    => $ver,
			),
			'root_element' => 'gravitysmtp-settings-app-root',
		);

		$this->register_app( $args );
	}

	protected function register_tools_app( $min, $ver ) {
		$args = array(
			'app_name'     => 'tools',
			'script_name'  => 'gravitysmtp_scripts_admin',
			'object_name'  => 'gravitysmtp_admin_config',
			'chunk'        => './tools',
			'enqueue'      => array( $this, 'should_enqueue_tools' ),
			'css'          => array(
				'handle' => 'tools_styles',
				'src'    => $this->plugin_url . "/assets/css/dist/tools{$min}.css",
				'deps'   => array( 'gravitysmtp_styles_base' ),
				'ver'    => $ver,
			),
			'root_element' => 'gravitysmtp-tools-app-root',
		);

		$this->register_app( $args );
	}

	public function should_enqueue_activity_log() {
		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page = htmlspecialchars( $page );

		return $page === 'gravitysmtp-activity-log';
	}

	public function should_enqueue_settings() {
		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page = htmlspecialchars( $page );

		return $page === 'gravitysmtp-settings';
	}

	public function should_enqueue_tools() {
		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page = htmlspecialchars( $page );

		return $page === 'gravitysmtp-tools';
	}

	protected function get_root_markup( $root ) {
		return '<div class="gravitysmtp-app" data-js="' . $root . '"><div class="gform-loader__mask gform-loader__mask--theme-light"><svg class="gform-loader gform-loader--ring"  height="64" width="64" viewBox="25 25 50 50" style="animation: 2s linear 0s infinite normal none running gformLoaderRotate; height: 64px; width: 64px;"><circle cx="50" cy="50" r="20" stroke-width="3.125" style="stroke: rgb(144, 146, 178);"></circle></svg></div></div>';
	}

	/**
	 * Use our custom action name to inject the app.
	 *
	 * @return string
	 */
	protected function get_inject_action() {
		return 'gravitysmtp_app_body';
	}

}
