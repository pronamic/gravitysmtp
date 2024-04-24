<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Config;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_Tools\Config;

class Apps_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function data() {
		$container         = Gravity_SMTP::container();
		$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$debug_log_enabled   = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_DEBUG_LOG_ENABLED, 'false' );
		$debug_log_enabled   = ! empty( $debug_log_enabled ) ? $debug_log_enabled !== 'false' : false;

		$test_mode = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_TEST_MODE );
		$test_mode = ! empty( $test_mode ) ? $test_mode !== 'false' : false;

		$usage_analytics_enabled = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_USAGE_ANALYTICS, 'true' );
		$usage_analytics_enabled = isset( $usage_analytics_enabled ) && $usage_analytics_enabled !== 'false';

		return array(
			'common'      => array(
				'i18n' => array(
					'aria_label_collapsed_metabox'               => esc_html__( 'Expand', 'gravitysmtp' ),
					'aria_label_expanded_metabox'                => esc_html__( 'Collapse', 'gravitysmtp' ),
					'confirm_change_cancel'                      => esc_html__( 'Cancel', 'gravitysmtp' ),
					'confirm_change_confirm'                     => esc_html__( 'Confirm', 'gravitysmtp' ),
					'debug_log_enabled'                          => esc_html__( 'Debug Log Enabled', 'gravitysmtp' ),
					'test_mode_enabled'                          => esc_html__( 'Test Mode Enabled', 'gravitysmtp' ),
					'debug_messages'                             => array(
						/* translators: %1$s is the body of the ajax request. */
						'deleting_all_debug_logs'           => esc_html__( 'Deleting all debug logs: %1$s', 'gravitysmtp' ),
						/* translators: %1$s is the error. */
						'deleting_all_debug_logs_error'     => esc_html__( 'Error deleting all debug logs: %1$s', 'gravitysmtp' ),
						/* translators: %1$s is the active connector they are saving settings for, %2$s is the body of the ajax request. */
						'saving_integration_settings'       => esc_html__( 'Saving integration settings for the %1$s connector: %2$s', 'gravitysmtp' ),
						/* translators: %1$s is the active connector they are saving settings for, %2$s is the error. */
						'saving_integration_settings_error' => esc_html__( 'Error saving integration settings for the %1$s connector: %2$s', 'gravitysmtp' ),
						/* translators: %1$s is the body of the ajax request. */
						'saving_plugin_settings'            => esc_html__( 'Saving plugin settings: %1$s', 'gravitysmtp' ),
						/* translators: %1$s is the error. */
						'saving_plugin_settings_error'      => esc_html__( 'Error saving plugin settings: %1$s', 'gravitysmtp' ),
					),
					'general_settings_usage_analytics_label'     => esc_html__( 'Share Gravity SMTP Analytics', 'gravitysmtp' ),
					/* translators: {{learn_link}} tags are replaced by opening and closing tags for a link to our learn more page for usage */
					'general_settings_usage_analytics_help_text' => esc_html__( 'We love improving the email sending experience for everyone in our community. By enabling analytics you can help us learn more about how our customers use Gravity SMTP. {{learn_link}}Learn more{{learn_link}}', 'gravitysmtp' ),
					'snackbar_api_save_success'                  => esc_html__( 'API settings saved.', 'gravitysmtp' ),
					'snackbar_generic_update_success'            => esc_html__( 'Setting successfully updated.', 'gravitysmtp' ),
					'snackbar_send_test_mail_error'              => esc_html__( 'Could not send test email; please check your logs.', 'gravitysmtp' ),
					'snackbar_send_test_mail_success'            => esc_html__( 'Email successfully sent.', 'gravitysmtp' ),
					'snackbar_activity_log_delete_error'         => esc_html__( 'Error deleting log entries.', 'gravitysmtp' ),
					'snackbar_email_log_error'                   => esc_html__( 'Error getting email log for requested page.', 'gravitysmtp' ),
					'snackbar_email_log_detail_generic_error'    => esc_html__( 'Error getting email log details.', 'gravitysmtp' ),
					'snackbar_email_log_detail_empty_error'      => esc_html__( 'Error getting email log details, the log data was empty.', 'gravitysmtp' ),
					'snackbar_email_log_delete_error'            => esc_html__( 'Error deleting email log.', 'gravitysmtp' ),
					'test_mode_warning_notice'                   => esc_html__( 'Test mode is enabled, emails will not be sent.', 'gravitysmtp' ),
				),
				'data' => array(
					'debug_log_enabled'       => $debug_log_enabled,
					'param_keys'              => array(
						'license_key'         => Save_Plugin_Settings_Endpoint::PARAM_LICENSE_KEY,
						'test_mode'           => Save_Plugin_Settings_Endpoint::PARAM_TEST_MODE,
						'event_log_enabled'   => Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_ENABLED,
						'event_log_retention' => Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_RETENTION,
						'usage_analytics'     => Save_Plugin_Settings_Endpoint::PARAM_USAGE_ANALYTICS,
					),
					'test_mode_enabled'       => $test_mode,
					'usage_analytics_enabled' => $usage_analytics_enabled,
					'usage_analytics_link'    => 'https://docs.gravitysmtp.com/about-additional-data-collection/',
				),
			),
			'hmr_dev'     => defined( 'GRAVITYSMTP_ENABLE_HMR' ) && GRAVITYSMTP_ENABLE_HMR,
			'public_path' => trailingslashit( Gravity_SMTP::get_base_url() ) . 'assets/js/dist/',
		);
	}

}
