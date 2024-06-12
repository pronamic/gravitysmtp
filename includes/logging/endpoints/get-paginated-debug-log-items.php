<?php

ini_set( 'html_errors', 0 );
define( 'SHORTINIT', true );

function run() {
	require '../../../../../../wp-load.php';
	require '../../models/class-debug-log-model.php';
	require '../../datastore/interface-data-store.php';
	require '../../datastore/class-plugin-opts-data-store.php';
	require '../../users/class-roles.php';
	require '../../utils/class-recipient.php';
	require '../../../vendor/gravityforms/gravity-tools/src/Logging/class-log-line.php';

	require_once( ABSPATH . WPINC . '/default-constants.php' );
	require_once( ABSPATH . WPINC . '/class-wp-textdomain-registry.php' );
	require_once( ABSPATH . WPINC . '/capabilities.php' );
	require_once( ABSPATH . WPINC . '/class-wp-session-tokens.php' );
	require_once( ABSPATH . WPINC . '/class-wp-user-meta-session-tokens.php' );
	require_once( ABSPATH . WPINC . '/class-wp-role.php' );
	require_once( ABSPATH . WPINC . '/class-wp-roles.php' );
	require_once( ABSPATH . WPINC . '/class-wp-user.php' );
	require_once( ABSPATH . WPINC . '/l10n.php' );
	require_once( ABSPATH . WPINC . '/user.php' );
	require_once( ABSPATH . WPINC . '/pluggable.php' );
	require_once( ABSPATH . WPINC . '/rest-api.php' );
	require_once( ABSPATH . WPINC . '/kses.php' );
	require_once( ABSPATH . WPINC . '/blocks.php' );
	require_once( ABSPATH . WPINC . '/theme.php' );

	wp_plugin_directory_constants();
	wp_cookie_constants();

	check_ajax_referer( 'debug_log_page', 'security' );

	$GLOBALS['wp_textdomain_registry'] = new WP_Textdomain_Registry();

	$per_page       = filter_input( INPUT_POST, 'per_page', FILTER_SANITIZE_NUMBER_INT );
	$requested_page = filter_input( INPUT_POST, 'requested_page', FILTER_SANITIZE_NUMBER_INT );
	$max_date       = filter_input( INPUT_POST, 'max_date' );
	$search_term    = filter_input( INPUT_POST, 'search_term' );
	$search_type    = filter_input( INPUT_POST, 'search_type' );
	$priority       = filter_input( INPUT_POST, 'priority' );

	if ( ! empty( $max_date ) ) {
		$max_date = htmlspecialchars( $max_date );
	}

	if ( ! empty( $search_term ) ) {
		$search_term = htmlspecialchars( $search_term );
	}

	if ( ! empty( $search_type ) ) {
		$search_type = htmlspecialchars( $search_type );
	}

	if ( ! empty( $priority ) ) {
		$priority = htmlspecialchars( $priority );
	}

	$requested_page = intval( $requested_page );
	$offset         = ( $requested_page - 1 ) * $per_page;

	if ( ! $max_date ) {
		$max_date = date( 'Y-m-d H:i:s', time() );
	}

	if ( empty( $per_page ) ) {
		$per_page = 20;
	}

	$debug_log_model = new \Gravity_Forms\Gravity_SMTP\Models\Debug_Log_Model();
	$rows            = $debug_log_model->paginate( $requested_page, $per_page, $max_date, $search_term, $search_type, $priority );
	$count           = $debug_log_model->count( $search_term, $search_type, $priority );

	$data = array(
		'rows'      => get_formatted_data_rows( $rows ),
		'total'     => $count,
		'row_count' => count( $rows ),
	);

	wp_send_json_success( $data );
}

function get_formatted_data_rows( $data ) {
	$debug_log_model = new \Gravity_Forms\Gravity_SMTP\Models\Debug_Log_Model();
	return $debug_log_model->lines_as_data_grid( $data );
}

function convert_dates_to_timezone( $date ) {
	$gmt_time   = new \DateTimeZone( 'UTC' );
	$local_time = new \DateTimeZone( wp_timezone_string() );
	$datetime   = new \DateTime( $date, $gmt_time );
	$datetime->setTimezone( $local_time );

	return $datetime->format( 'F d, Y \a\t h:ia' );
}

function get_grid_actions( $event_id ) {
	$actions = array(
		'component'  => 'Box',
		'components' => array(
			array(
				'component' => 'Button',
				'props'     => array(
					'action'        => 'view',
					'customAttributes' => array(
						'title' => esc_html__( 'View email log', 'gravitysmtp' ),
					),
					'customClasses' => array( 'gravitysmtp-data-grid__action' ),
					'icon'          => 'eye',
					'iconPrefix'    => 'gravitysmtp-admin-icon',
					'spacing'       => [ 0, 2, 0, 0 ],
					'size'          => 'size-height-s',
					'type'          => 'icon-white',
					'data'          => array(
						'event_id' => $event_id,
					),
					'disabled' => ! current_user_can( \Gravity_Forms\Gravity_SMTP\Users\Roles::VIEW_EMAIL_LOG_DETAILS ),
				),
			),
			array(
				'component' => 'Button',
				'props'     => array(
					'action'        => 'preview',
					'customAttributes' => array(
						'title' => esc_html__( 'View email', 'gravitysmtp' ),
					),
					'customClasses' => array( 'gravitysmtp-data-grid__action' ),
					'icon'          => 'mail',
					'iconPrefix'    => 'gravitysmtp-admin-icon',
					'spacing'       => [ 0, 2, 0, 0 ],
					'size'          => 'size-height-s',
					'type'          => 'icon-white',
					'data'          => array(
						'event_id' => $event_id,
					),
					'disabled' => ! current_user_can( \Gravity_Forms\Gravity_SMTP\Users\Roles::VIEW_EMAIL_LOG_PREVIEW ),
				),
			),
			array(
				'component' => 'Button',
				'props'     => array(
					'action'        => 'delete',
					'customAttributes' => array(
						'title' => esc_html__( 'Delete email log', 'gravitysmtp' ),
					),
					'customClasses' => array( 'gravitysmtp-data-grid__action' ),
					'icon'          => 'trash',
					'iconPrefix'    => 'gravitysmtp-admin-icon',
					'size'          => 'size-height-s',
					'type'          => 'icon-white',
					'data'          => array(
						'event_id' => $event_id,
					),
					'disabled' => ! current_user_can( \Gravity_Forms\Gravity_SMTP\Users\Roles::DELETE_EMAIL_LOG ),
				),
			),
		),
	);

	return apply_filters( 'gravitysmtp_email_log_actions', $actions );
}

run();
