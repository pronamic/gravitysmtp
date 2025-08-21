<?php

namespace Gravity_Forms\Gravity_SMTP\Notifications;

use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_Tools\Emails\Email_Templatizer;

class Email_Summary_Handler {

	const ACTION_NAME = 'gravitysmtp_email_summary';
	const RUN_OPTION_NAME = 'gravitysmtp_email_summary_last_run';

	protected $plugin_data_store;
	protected $model;
	protected $start;
	protected $end;

	public function __construct( Data_Store_Router $plugin_data_store, Event_Model $model ) {
		$this->plugin_data_store = $plugin_data_store;
		$this->model             = $model;

		$mod_string   = sprintf( '-%d days', $this->plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_NOTIFICATIONS_EMAIL_DIGEST_FREQUENCY, '7' ) );
		$this->start  = gmdate( 'Y-m-d 00:00:00', strtotime( $mod_string ) );
		$this->end    = gmdate( 'Y-m-d 23:59:59', strtotime( '+1 day' ) );
	}

	public function handle( $respect_cron = true ) {
		$enabled = $this->plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_NOTIFICATIONS_EMAIL_DIGEST_ENABLED, false );

		// Don't process if the setting isn't enabled.
		if ( ! $enabled ) {
			return;
		}

		$last_run = get_option( self::RUN_OPTION_NAME, false );

		$mod_string    = sprintf( ' +%d days', $this->plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_NOTIFICATIONS_EMAIL_DIGEST_FREQUENCY, '7' ) );
		$last_run_date = gmdate( 'Y-m-d h:i:s', $last_run );
		$run_limit     = strtotime( $last_run_date . $mod_string );

		// Hasn't been long enough between runs.
		if ( $respect_cron && ! empty( $last_run ) && $run_limit > time() ) {
			return;
		}

		update_option( self::RUN_OPTION_NAME, time() );

		$markup = file_get_contents( trailingslashit( __DIR__ ) . '../../email-templates/email-digest-template.html' );
		$data   = array();

		$user         = wp_get_current_user();
		$data['user'] = array(
			'name'   => $user->display_name ? $user->display_name : $user->user_login,
			'avatar' => get_avatar_url( $user->ID, array( 'size' => 108 ) ),
		);

		$data['year']      = date( 'Y' );
		$frequency         = $this->plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_NOTIFICATIONS_EMAIL_DIGEST_FREQUENCY, '7' );
		$data['frequency'] = $frequency;
		switch ( $frequency ) {
			case '1':
				$data['frequency_plural']   = __( 'daily', 'gravitysmtp' );
				$data['frequency_singular'] = __( 'day', 'gravitysmtp' );
				break;

			case '7':
				$data['frequency_plural']   = __( 'weekly', 'gravitysmtp' );
				$data['frequency_singular'] = __( 'week', 'gravitysmtp' );
				break;

			case '30':
				$data['frequency_plural']   = __( 'monthly', 'gravitysmtp' );
				$data['frequency_singular'] = __( 'month', 'gravitysmtp' );
				break;
			default:
				$data['frequency_plural']   = $data['frequency'];
				$data['frequency_singular'] = $data['frequency'] . ' day(s)';
				break;
		}

		$data['images'] = array(
			'arrow_bg'   => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/images/email-templates/gravitysmtp-arrow-bg.png',
			'gsmtp_logo' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/images/email-templates/gravitysmtp-email-logo.png',
		);

		$data['stats']               = $this->get_email_basic_stats();
		$data['stats']['recipients'] = $this->get_recipients();
		$data['stats']['sources']    = $this->get_sources();

		$data['stats']['source_ph'] = array();
		$data['stats']['recipient_ph'] = array();

		if ( count( $data['stats']['recipients'] ) > count( $data['stats']['sources'] ) ) {
			$diff = count( $data['stats']['recipients'] ) - count( $data['stats']['sources'] );
			for( $i = 0; $i < $diff; $i++ ) {
				$data['stats']['source_ph'][] = $i;
			}
		}

		if ( count( $data['stats']['sources'] ) > count( $data['stats']['recipients'] ) ) {
			$diff = count( $data['stats']['sources'] ) - count( $data['stats']['recipients'] );
			for( $i = 0; $i < $diff; $i++ ) {
				$data['stats']['recipient_ph'][] = $i;
			}
		}

		$data['links'] = array(
			'sent'    => admin_url( 'admin.php?page=gravitysmtp-activity-log&log_page=1&filters=eyJzdGF0dXMiOlsic2VudCJdfQ%3D%3D' ),
			'failed'  => admin_url( 'admin.php?page=gravitysmtp-activity-log&log_page=1&filters=eyJzdGF0dXMiOlsiZmFpbGVkIl19' ),
			'setting' => admin_url( 'admin.php?page=gravitysmtp-settings#email-activity-digest' ),
		);

		$site_link = sprintf(
			'<a href="%s" style="color: #5b5e80; font-weight: 500; text-decoration: underline;">%s</a>',
			esc_url( home_url() ),
			esc_html( parse_url( home_url(), PHP_URL_HOST ) )
		);

		$data['strings'] = array(
			'gravity_smtp'               => __( 'Gravity SMTP', 'gravitysmtp' ),
			'title'                      => sprintf(
				/* translators: %s: frequency plural (e.g., "daily", "weekly", "monthly") */
				__( 'Your %s email activity digest - Gravity SMTP', 'gravitysmtp' ),
				$data['frequency_plural']
			),
			'preheader'                  => sprintf(
				/* translators: 1: frequency plural (e.g., "daily", "weekly", "monthly"), 2: frequency singular (e.g., "day", "week", "month") */
				__( 'Your %s email activity digest is ready! See how your emails performed in the past %s.', 'gravitysmtp' ),
				$data['frequency_plural'],
				$data['frequency_singular']
			),
			'greeting'                   => sprintf(
				/* translators: %s: user's name */
				__( 'Hi %s,', 'gravitysmtp' ),
				$data['user']['name']
			),
			'intro_message'              => sprintf(
				/* translators: 1: site name, 2: frequency singular (e.g., "day", "week", "month") */
				__( 'Here is how your emails from %s performed in the past %s.', 'gravitysmtp' ),
				$site_link,
				$data['frequency_singular']
			),
			'sent_label'                 => __( 'Sent', 'gravitysmtp' ),
			'failed_label'               => __( 'Failed', 'gravitysmtp' ),
			'view_sent_button'           => __( 'View Sent', 'gravitysmtp' ),
			'view_failed_button'         => __( 'View Failed', 'gravitysmtp' ),
			'top_email_recipients_title' => __( 'Top Email Recipients', 'gravitysmtp' ),
			'top_sending_sources_title'  => __( 'Top Sending Sources', 'gravitysmtp' ),
			'footer'                     => sprintf(
				/* translators: %s: site name */
				__( 'You\'re receiving this report from Gravity SMTP for %s.', 'gravitysmtp' ),
				$site_link
			),
			'setting'                    => sprintf(
				'<a href="%s" style="color: #5b5e80; text-decoration: underline;">%s</a>.',
				$data['links']['setting'],
				__( 'Manage your Email Activity Digest settings', 'gravitysmtp' )
			),
			'copyright'                  => sprintf(
				/* translators: %s: copyright year */
				__( 'Copyright &copy; %s', 'gravitysmtp' ),
				$data['year']
			),
		);

		$template     = new Email_Templatizer( $markup );
		$email_markup = $template->render( $data );

		$admin_email  = get_option( 'admin_email' );
		$content_type = 'text/html';

		$headers = array(
			'content-type' => 'Content-type: ' . $content_type,
			'from'         => 'From: ' . $admin_email,
		);

		wp_mail( array( 'email' => $admin_email ), __( 'Your Gravity SMTP Email Digest', 'gravitysmtp' ), $email_markup, $headers, array(), 'Gravity SMTP Email Digest' );
	}

	protected function get_email_basic_stats() {
		$stats        = $this->model->get_event_stats( $this->start, $this->end );

		$old_start = date_create( $this->start );
		date_sub( $old_start, date_interval_create_from_date_string( '7 days' ) );
		$old_start = date_format( $old_start, 'Y-m-d 00:00:00' );

		$old_end = date_create( $this->end );
		date_sub( $old_end, date_interval_create_from_date_string( '9 days' ) );
		$old_end = date_format( $old_end, 'Y-m-d h:i:s' );

		$old_stats = $this->model->get_event_stats( $old_start, $old_end );

		$sent     = isset( $stats['sent'] ) ? $stats['sent'] : 0;
		$old_sent = isset( $old_stats['sent'] ) ? $old_stats['sent'] : 0;

		$failed     = isset( $stats['failed'] ) ? $stats['failed'] : 0;
		$old_failed = isset( $old_stats['failed'] ) ? $old_stats['failed'] : 0;

		if ( $old_sent === 0 ) {
			$sent_change = $sent * 100;
		} else {
			$sent_change = ( ( $sent - $old_sent ) / abs( $old_sent ) ) * 100;
		}

		if ( $old_failed === 0 ) {
			$failed_change = $failed * 100;
		} else {
			$failed_change = ( ( $failed - $old_failed ) / abs( $old_failed ) ) * 100;
		}

		return array(
			'sent'                    => number_format_i18n( $sent ),
			'sent_change'             => number_format_i18n( round( $sent_change ) ),
			'sent_change_direction'   => $sent > $old_sent ? '&uarr;' : '&darr;',
			'sent_change_color_bg'    => $sent > $old_sent ? '#e1f6ed' : '#fee4e2',
			'sent_change_color_fg'    => $sent > $old_sent ? '#276a52' : '#c02b0a',
			'failed'                  => number_format_i18n( $failed ),
			'failed_change'           => number_format_i18n( round( $failed_change ) ),
			'failed_change_direction' => $failed > $old_failed ? '&uarr;' : '&darr;',
			'failed_change_color_bg'  => $failed < $old_failed ? '#e1f6ed' : '#fee4e2',
			'failed_change_color_fg'  => $failed < $old_failed ? '#276a52' : '#c02b0a',
		);
	}

	protected function get_recipients() {
		$recipients = $this->model->get_top_recipients( $this->start, $this->end );
		array_walk( $recipients, function ( &$item ) {
			$hash                    = hash( 'sha256', $item['recipients'] );
			$item['img']             = sprintf( 'https://gravatar.com/avatar/%s', $hash );
			$item['count']           = $item['total'];
			$item['count_formatted'] = sprintf( __( '%d Emails', 'gravitysmtp' ), number_format_i18n( $item['total'] ) );
			$item['email']           = strlen( $item['recipients'] ) > 15 ? substr( $item['recipients'], 0, 15 ) . '&hellip;' : $item['recipients'];

			unset( $item['recipients'] );
			unset( $item['total'] );
		} );

		$recipients = array_filter( $recipients, function ( $data ) {
			return $data['email'] !== 'headers' && $data['email'] !== 'message_omitted' && ! empty( $data['email'] );
		} );

		return array_values( $recipients );
	}

	protected function get_sources() {
		$sources               = $this->model->get_top_sending_sources( $this->start, $this->end );
		$base_source_image_url = plugin_dir_url( dirname( __DIR__ ) ) . 'assets/images/plugin-icons/';
		$base_source_image_dir = plugin_dir_path( dirname( __DIR__ ) ) . 'assets/images/plugin-icons/';

		array_walk( $sources, function ( &$item ) use ( $base_source_image_url, $base_source_image_dir ) {
			$new_item['name']            = strlen( $item['source'] ) > 15 ? substr( $item['source'], 0, 15 ) . '&hellip;' : $item['source'];
			$new_item['count']           = $item['total'];
			$new_item['count_formatted'] = sprintf( __( '%d Emails', 'gravitysmtp' ), number_format_i18n( $item['total'] ) );

			$source     = $item['source'];
			$source_img = $base_source_image_dir . sanitize_title( $source ) . '-92.png';

			if ( ! file_exists( $source_img ) ) {
				$new_item['img'] = $base_source_image_url . 'default-92.png';
			} else {
				$new_item['img'] = $base_source_image_url . sanitize_title( $source ) . '-92.png';
			}

			$item = $new_item;
		} );
		// Some old entries are missing the source param and return the `headers` instead.
		$sources = array_filter( $sources, function ( $data ) {
			return $data['name'] !== 'headers' && $data['name'] !== 'message_omitted';
		} );

		return array_values( $sources );
	}
}
