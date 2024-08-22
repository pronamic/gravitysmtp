<?php

namespace Gravity_Forms\Gravity_SMTP\Users;

class Roles {

	const VIEW_EMAIL_LOG   = 'gravitysmtp_view_email_log';
	const EDIT_EMAIL_LOG   = 'gravitysmtp_edit_email_log';
	const DELETE_EMAIL_LOG = 'gravitysmtp_delete_email_log';

	const VIEW_EMAIL_LOG_DETAILS   = 'gravitysmtp_view_email_log_details';
	const EDIT_EMAIL_LOG_DETAILS   = 'gravitysmtp_edit_email_log_details';
	const DELETE_EMAIL_LOG_DETAILS = 'gravitysmtp_delete_email_log_details';
	const VIEW_EMAIL_LOG_PREVIEW   = 'gravitysmtp_view_email_log_preview';

	const VIEW_GENERAL_SETTINGS = 'gravitysmtp_view_general_settings';
	const EDIT_GENERAL_SETTINGS = 'gravitysmtp_edit_general_settings';

	const VIEW_EMAIL_MANAGEMENT_SETTINGS = 'gravitysmtp_view_email_management_settings';
	const EDIT_EMAIL_MANAGEMENT_SETTINGS = 'gravitysmtp_edit_email_management_settings';

	const VIEW_LICENSE_KEY = 'gravitysmtp_view_license_key';
	const EDIT_LICENSE_KEY = 'gravitysmtp_edit_license_key';

	const VIEW_TEST_MODE = 'gravitysmtp_view_test_mode';
	const EDIT_TEST_MODE = 'gravitysmtp_edit_test_mode';

	const VIEW_USAGE_ANALYTICS = 'gravitysmtp_view_usage_analytics';
	const EDIT_USAGE_ANALYTICS = 'gravitysmtp_edit_usage_analytics';

	const VIEW_UNINSTALL = 'gravitysmtp_view_uninstall';
	const EDIT_UNINSTALL = 'gravitysmtp_edit_uninstall';

	const VIEW_INTEGRATIONS = 'gravitysmtp_view_integrations';
	const EDIT_INTEGRATIONS = 'gravitysmtp_edit_integrations';

	const VIEW_EMAIL_LOG_SETTINGS = 'gravitysmtp_view_email_log_settings';
	const EDIT_EMAIL_LOG_SETTINGS = 'gravitysmtp_edit_email_log_settings';

	const VIEW_DEBUG_LOG_SETTINGS = 'gravitysmtp_view_debug_log_settings';
	const EDIT_DEBUG_LOG_SETTINGS = 'gravitysmtp_edit_debug_log_settings';

	const VIEW_DEBUG_LOG   = 'gravitysmtp_view_debug_log';
	const EDIT_DEBUG_LOG   = 'gravitysmtp_edit_debug_log';
	const DELETE_DEBUG_LOG = 'gravitysmtp_delete_debug_log';

	const VIEW_TOOLS              = 'gravitysmtp_view_tools';
	const VIEW_TOOLS_SENDATEST    = 'gravitysmtp_view_tools_sendatest';
	const VIEW_TOOLS_SYSTEMREPORT = 'gravitysmtp_view_tools_systemreport';

	private $caps = array(
		self::VIEW_EMAIL_LOG,
		self::EDIT_EMAIL_LOG,
		self::DELETE_EMAIL_LOG,
		self::VIEW_EMAIL_LOG_DETAILS,
		self::EDIT_EMAIL_LOG_DETAILS,
		self::DELETE_EMAIL_LOG_DETAILS,
		self::VIEW_EMAIL_LOG_PREVIEW,
		self::VIEW_GENERAL_SETTINGS,
		self::EDIT_GENERAL_SETTINGS,
		self::VIEW_EMAIL_MANAGEMENT_SETTINGS,
		self::EDIT_EMAIL_MANAGEMENT_SETTINGS,
		self::VIEW_LICENSE_KEY,
		self::EDIT_LICENSE_KEY,
		self::VIEW_TEST_MODE,
		self::EDIT_TEST_MODE,
		self::VIEW_USAGE_ANALYTICS,
		self::EDIT_USAGE_ANALYTICS,
		self::VIEW_UNINSTALL,
		self::EDIT_UNINSTALL,
		self::VIEW_INTEGRATIONS,
		self::EDIT_INTEGRATIONS,
		self::VIEW_EMAIL_LOG_SETTINGS,
		self::EDIT_EMAIL_LOG_SETTINGS,
		self::VIEW_DEBUG_LOG_SETTINGS,
		self::EDIT_DEBUG_LOG_SETTINGS,
		self::DELETE_EMAIL_LOG,
		self::VIEW_DEBUG_LOG,
		self::EDIT_DEBUG_LOG,
		self::DELETE_DEBUG_LOG,
		self::VIEW_TOOLS,
		self::VIEW_TOOLS_SENDATEST,
		self::VIEW_TOOLS_SYSTEMREPORT,
	);

	public function register() {
		$admin = get_role( 'administrator' );
		foreach ( $this->caps as $cap ) {
			$admin->add_cap( $cap );
		}
	}

}
