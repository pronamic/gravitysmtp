<?php

namespace Gravity_Forms\Gravity_SMTP\Users;

use Gravity_Forms\Gravity_SMTP\Environment\Environment_Details;

/**
 * Handles integration with the Members plugin for capability management.
 *
 * @since 2.1.1
 */
class Members_Integration {

	/**
	 * Register the Gravity SMTP capabilities group with the Members plugin.
	 *
	 * @since 2.1.1
	 * @access public
	 */
	public function register() {

		if ( $this->is_members_page() ) {
			add_action( 'admin_enqueue_scripts', function () {
				$version = ( new Environment_Details() )->get_version();
				$min     = ( new Environment_Details() )->get_min();
				wp_register_style( 'gravitysmtp_styles_members_icons', \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::get_base_url() . "/assets/css/dist/admin-icons{$min}.css", null, $version );
				wp_enqueue_style( 'gravitysmtp_styles_members_icons' );
			} );
		}
		if ( function_exists( 'members_register_cap_group' ) && function_exists( 'members_register_cap' ) ) {
			add_action( 'members_register_cap_groups', array( $this, 'members_register_cap_group' ), 97 );
			add_action( 'members_register_caps', array( $this, 'members_register_caps' ) );
		}
    }

	/**
	 * Register the Gravity SMTP capabilities group with the Members plugin.
	 *
	 * @since 2.1.1
	 * @access public
	 */
	public function members_register_cap_group() {
		members_register_cap_group(
			'gravitysmtp',
			array(
				'label' => esc_html__( 'Gravity SMTP', 'gravitysmtp' ),
				'icon'  => 'gravitysmtp-admin-icon gravitysmtp-admin-icon--dashboard-icon',
				'caps'  => array(),
			)
		);
	}

	/**
	 * Register the Gravity SMTP capabilities and their human readable labels with the Members plugin.
	 *
	 * @since 2.1.1
	 * @access public
	 */
	public function members_register_caps() {
		foreach ( $this->get_members_caps() as $cap => $label ) {
			members_register_cap(
				$cap,
				array(
					'label' => $label,
					'group' => 'gravitysmtp',
				)
			);
		}
	}

	/**
	 * Get the capabilities for Members plugin integration.
	 *
	 * @since 2.1.1
	 * @access private
	 *
	 * @return array
	 */
	private function get_members_caps() {
		return array(
			Roles::DELETE_DEBUG_LOG                => esc_html__( 'Delete Debug Log', 'gravitysmtp' ),
			Roles::DELETE_EMAIL_LOG                => esc_html__( 'Delete Email Log', 'gravitysmtp' ),
			Roles::DELETE_EMAIL_LOG_DETAILS        => esc_html__( 'Delete Email Log Details', 'gravitysmtp' ),
			Roles::EDIT_ALERTS                     => esc_html__( 'Edit Alerts', 'gravitysmtp' ),
			Roles::EDIT_ALERTS_SLACK_SETTINGS      => esc_html__( 'Edit Alerts Slack Settings', 'gravitysmtp' ),
			Roles::EDIT_ALERTS_TWILIO_SETTINGS     => esc_html__( 'Edit Alerts Twilio Settings', 'gravitysmtp' ),
			Roles::EDIT_DEBUG_LOG                  => esc_html__( 'Edit Debug Log', 'gravitysmtp' ),
			Roles::EDIT_DEBUG_LOG_SETTINGS         => esc_html__( 'Edit Debug Log Settings', 'gravitysmtp' ),
			Roles::EDIT_EMAIL_LOG                  => esc_html__( 'Edit Email Log', 'gravitysmtp' ),
			Roles::EDIT_EMAIL_LOG_DETAILS          => esc_html__( 'Edit Email Log Details', 'gravitysmtp' ),
			Roles::EDIT_EMAIL_LOG_SETTINGS         => esc_html__( 'Edit Email Log Settings', 'gravitysmtp' ),
			Roles::EDIT_EMAIL_MANAGEMENT_SETTINGS  => esc_html__( 'Edit Email Management Settings', 'gravitysmtp' ),
			Roles::EDIT_GENERAL_SETTINGS           => esc_html__( 'Edit General Settings', 'gravitysmtp' ),
			Roles::EDIT_INTEGRATIONS               => esc_html__( 'Edit Integrations', 'gravitysmtp' ),
			Roles::EDIT_LICENSE_KEY                => esc_html__( 'Edit License Key', 'gravitysmtp' ),
			Roles::EDIT_NOTIFICATIONS_SETTINGS     => esc_html__( 'Edit Notifications Settings', 'gravitysmtp' ),
			Roles::EDIT_TEST_MODE                  => esc_html__( 'Edit Test Mode', 'gravitysmtp' ),
			Roles::EDIT_UNINSTALL                  => esc_html__( 'Edit Uninstall', 'gravitysmtp' ),
			Roles::EDIT_USAGE_ANALYTICS            => esc_html__( 'Edit Usage Analytics', 'gravitysmtp' ),
			Roles::VIEW_ALERTS                     => esc_html__( 'View Alerts', 'gravitysmtp' ),
			Roles::VIEW_ALERTS_SLACK_SETTINGS      => esc_html__( 'View Alerts Slack Settings', 'gravitysmtp' ),
			Roles::VIEW_ALERTS_TWILIO_SETTINGS     => esc_html__( 'View Alerts Twilio Settings', 'gravitysmtp' ),
			Roles::VIEW_DASHBOARD                  => esc_html__( 'View Dashboard', 'gravitysmtp' ),
			Roles::VIEW_DEBUG_LOG                  => esc_html__( 'View Debug Log', 'gravitysmtp' ),
			Roles::VIEW_DEBUG_LOG_SETTINGS         => esc_html__( 'View Debug Log Settings', 'gravitysmtp' ),
			Roles::VIEW_EMAIL_LOG                  => esc_html__( 'View Email Log', 'gravitysmtp' ),
			Roles::VIEW_EMAIL_LOG_DETAILS          => esc_html__( 'View Email Log Details', 'gravitysmtp' ),
			Roles::VIEW_EMAIL_LOG_PREVIEW          => esc_html__( 'View Email Log Preview', 'gravitysmtp' ),
			Roles::VIEW_EMAIL_LOG_SETTINGS         => esc_html__( 'View Email Log Settings', 'gravitysmtp' ),
			Roles::VIEW_EMAIL_MANAGEMENT_SETTINGS  => esc_html__( 'View Email Management Settings', 'gravitysmtp' ),
			Roles::VIEW_GENERAL_SETTINGS           => esc_html__( 'View General Settings', 'gravitysmtp' ),
			Roles::VIEW_INTEGRATIONS               => esc_html__( 'View Integrations', 'gravitysmtp' ),
			Roles::VIEW_LICENSE_KEY                => esc_html__( 'View License Key', 'gravitysmtp' ),
			Roles::VIEW_NOTIFICATIONS_SETTINGS     => esc_html__( 'View Notifications Settings', 'gravitysmtp' ),
			Roles::VIEW_TEST_MODE                  => esc_html__( 'View Test Mode', 'gravitysmtp' ),
			Roles::VIEW_TOOLS                      => esc_html__( 'View Tools', 'gravitysmtp' ),
			Roles::VIEW_TOOLS_SENDATEST            => esc_html__( 'View Tools Send a Test', 'gravitysmtp' ),
			Roles::VIEW_TOOLS_SYSTEMREPORT         => esc_html__( 'View Tools System Report', 'gravitysmtp' ),
			Roles::VIEW_UNINSTALL                  => esc_html__( 'View Uninstall', 'gravitysmtp' ),
			Roles::VIEW_USAGE_ANALYTICS            => esc_html__( 'View Usage Analytics', 'gravitysmtp' ),
			Roles::VIEW_EMAIL_SUPPRESSION_SETTINGS => esc_html__( 'View Email Suppression Settings', 'gravitysmtp' ),
			Roles::EDIT_EMAIL_SUPPRESSION_SETTINGS => esc_html__( 'Edit Email Suppression Settings', 'gravitysmtp' ),
			Roles::VIEW_EXPERIMENTAL_FEATURES      => esc_html__( 'View Experimental Features', 'gravitysmtp' ),
			Roles::EDIT_EXPERIMENTAL_FEATURES      => esc_html__( 'Edit Experimental Features', 'gravitysmtp' ),
		);
	}

	/**
	 * Check if the current page is a Members plugin page.
	 *
	 * @since 2.1.1
	 * @access private
	 *
	 * @return bool
	 */
	private function is_members_page() {
		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page = htmlspecialchars( $page );

		return strncmp( $page, 'roles', 5 ) === 0 || strncmp( $page, 'members', 7 ) === 0;
	}

}