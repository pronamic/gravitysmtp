<?php

ini_set( 'html_errors', 0 );
define( 'SHORTINIT', true );

require '../../utils/class-fast-endpoint.php';

class Get_Paginated_Log_Items extends \Gravity_Forms\Gravity_SMTP\Utils\Fast_Endpoint {
	public function run() {
		check_ajax_referer( 'activity_log_page', 'security' );

		$per_page       = filter_input( INPUT_POST, 'per_page', FILTER_SANITIZE_NUMBER_INT );
		$requested_page = filter_input( INPUT_POST, 'requested_page', FILTER_SANITIZE_NUMBER_INT );
		$max_date       = filter_input( INPUT_POST, 'max_date' );
		$search_term    = filter_input( INPUT_POST, 'search_term' );
		$search_type    = filter_input( INPUT_POST, 'search_type' );
		$sort_by        = filter_input( INPUT_POST, 'sort_by' );
		$sort_order     = filter_input( INPUT_POST, 'sort_order' );
		$filters        = filter_input( INPUT_POST, 'filters', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! empty( $max_date ) ) {
			$max_date = htmlspecialchars( $max_date );
		}

		if ( ! empty( $search_term ) ) {
			$search_term = htmlspecialchars( $search_term );
		}

		if ( ! empty( $search_type ) ) {
			$search_type = htmlspecialchars( $search_type );
		}

		if ( ! empty( $sort_by ) ) {
			$sort_by = htmlspecialchars( $sort_by );
		}

		if ( ! empty( $sort_order ) ) {
			$sort_order = htmlspecialchars( $sort_order );
		}

		$requested_page = intval( $requested_page );
		$offset         = ( $requested_page - 1 ) * $per_page;

		if ( ! $max_date ) {
			$max_date = date( 'Y-m-d H:i:s', time() );
		}

		if ( empty( $per_page ) ) {
			$per_page = 20;
		}

		if ( ! is_array( $filters ) || empty( $filters ) ) {
			$filters = array();
		}

		$event_model = new \Gravity_Forms\Gravity_SMTP\Models\Event_Model( new \Gravity_Forms\Gravity_SMTP\Models\Hydrators\Hydrator_Factory(), new \Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store(), new \Gravity_Forms\Gravity_SMTP\Utils\Recipient_Parser(), new \Gravity_Forms\Gravity_SMTP\Utils\SQL_Filter_Parser() );
		$rows        = $event_model->paginate( $requested_page, $per_page, $max_date, $search_term, $search_type, $sort_by, $sort_order, $filters );
		$count       = $event_model->count( $search_term, $search_type, $filters );

		$data = array(
			'rows'      => $this->get_formatted_data_rows( $rows ),
			'total'     => $count,
			'row_count' => count( $rows ),
		);

		wp_send_json_success( $data );
	}

	protected function extra_includes() {
		return array(
			'../../models/traits/trait-can-compare-dynamically.php',
			'../../models/class-event-model.php',
			'../../models/hydrators/class-hydrator-factory.php',
			'../../datastore/interface-data-store.php',
			'../../datastore/class-plugin-opts-data-store.php',
			'../../users/class-roles.php',
			'../../utils/class-recipient.php',
			'../../utils/class-recipient-collection.php',
			'../../utils/class-recipient-parser.php',
			'../../utils/class-sql-filter-parser.php',
			'../../enums/class-integration-enum.php',
			'../../enums/class-status-enum.php',
			'../../models/hydrators/interface-hydrator.php',
			'../../models/hydrators/class-hydrator-wp-mail.php',
			'../../models/hydrators/class-hydrator-brevo.php',
			'../../models/hydrators/class-hydrator-generic.php',
			'../../models/hydrators/class-hydrator-mailgun.php',
			'../../models/hydrators/class-hydrator-postmark.php',
			'../../models/hydrators/class-hydrator-sendgrid.php',
		);
	}

	private function get_formatted_data_rows( $data ) {
		$rows             = array();
		$recipient_parser = new \Gravity_Forms\Gravity_SMTP\Utils\Recipient_Parser();

		foreach ( $data as $row ) {
			$grid_actions = $this->get_grid_actions( $row );
			$extra        = strpos( $row['extra'], '{' ) === 0 ? json_decode( $row['extra'], true ) : unserialize( $row['extra'] );
			$to           = isset( $extra['to'] ) ? $extra['to'] : '';
			$to_address   = $recipient_parser->parse( $to )->first()->email();
			$more_count   = max( 0, $row['email_counts'] - 1 );

			$rows[] = array(
				'id'          => $row['id'],
				'subject'     => array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'view',
						'customClasses' => array( 'gravitysmtp-data-grid__subject' ),
						'label'         => $row['subject'],
						'type'          => 'unstyled',
						'data'          => array(
							'event_id' => $row['id'],
						),
					),
				),
				'status'      => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => \Gravity_Forms\Gravity_SMTP\Enums\Status_Enum::label( $row['status'] ),
						'status' => \Gravity_Forms\Gravity_SMTP\Enums\Status_Enum::indicator( $row['status'] ),
						'hasDot' => false,
					),
				),
				'to'          => array(
					'component'  => 'Box',
					'props'      => array(
						'customClasses' => array( 'gravitysmtp-activity-log-app__activity-log-table-recipient' ),
						'display'       => 'flex',
					),
					'components' => array(
						array(
							'component'  => 'Box',
							'props'      => array(
								'customClasses' => array( 'gravitysmtp-activity-log-app__activity-log-table-recipient-meta' ),
								'display'       => 'flex',
							),
							'components' => array(
								array(
									'component' => 'Gravatar',
									'props'     => array(
										'circular'      => true,
										'customClasses' => array( 'gravitysmtp-activity-log-app__activity-log-table-recipient-gravatar' ),
										'defaultImage'  => 'mp',
										'emailHash'     => hash( 'sha256', $to_address ),
										'height'        => 24,
										'width'         => 24,
									),
								),
								array(
									'component' => $more_count > 0 ? 'Text' : null,
									'props'     => array(
										'content'       => '+' . (string) $more_count,
										'customClasses' => array( 'gravitysmtp-activity-log-app__activity-log-table-recipient-more' ),
										'size'          => 'text-xxs',
									),
								),
							),
						),
						array(
							'component' => 'Text',
							'props'     => array(
								'content'       => $to_address,
								'customClasses' => array( 'gravitysmtp-activity-log-app__activity-log-table-recipient-email' ),
								'size'          => 'text-sm',
							),
						),
					),
				),
				'opened'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => $row['opened'],
						'size'    => 'text-sm',
					),
				),
				'source'      => array(
					'component' => 'Text',
					'props'     => array(
						'content' => $row['source'],
						'size'    => 'text-sm',
					),
				),
				'integration' => array(
					'external' => true,
					'key'      => $row['service'] . '_logo',
					'props'    => array(
						'height' => 24,
						'title'  => \Gravity_Forms\Gravity_SMTP\Enums\Integration_Enum::svg_title( $row['service'] ),
						'width'  => 24,
					),
				),
				'date'        => array(
					'component' => 'Text',
					'props'     => array(
						'content' => $this->convert_dates_to_timezone( $row['date_updated'] ),
						'size'    => 'text-sm',
					),
				),
				'actions'     => $grid_actions,
			);
		}

		return $rows;
	}

	private function convert_dates_to_timezone( $date ) {
		$gmt_time   = new \DateTimeZone( 'UTC' );
		$local_time = new \DateTimeZone( wp_timezone_string() );
		$datetime   = new \DateTime( $date, $gmt_time );
		$datetime->setTimezone( $local_time );

		return $datetime->format( 'F d, Y \a\t h:ia' );
	}

	private function get_grid_actions( $row ) {
		$actions = array(
			'component'  => 'Box',
			'components' => array(
				array(
					'component' => 'Button',
					'props'     => array(
						'action'           => 'view',
						'customAttributes' => array(
							'title' => esc_html__( 'View email log', 'gravitysmtp' ),
						),
						'customClasses'    => array( 'gravitysmtp-data-grid__action' ),
						'icon'             => 'eye',
						'iconPrefix'       => 'gravitysmtp-admin-icon',
						'spacing'          => [ 0, 2, 0, 0 ],
						'size'             => 'size-height-s',
						'type'             => 'icon-white',
						'data'             => array(
							'event_id' => $row['id'],
						),
						'disabled'         => ! current_user_can( \Gravity_Forms\Gravity_SMTP\Users\Roles::VIEW_EMAIL_LOG_DETAILS ),
					),
				),
				array(
					'component' => 'Button',
					'props'     => array(
						'action'           => 'preview',
						'customAttributes' => array(
							'title' => esc_html__( 'View email', 'gravitysmtp' ),
						),
						'customClasses'    => array( 'gravitysmtp-data-grid__action' ),
						'icon'             => 'mail',
						'iconPrefix'       => 'gravitysmtp-admin-icon',
						'spacing'          => [ 0, 2, 0, 0 ],
						'size'             => 'size-height-s',
						'type'             => 'icon-white',
						'data'             => array(
							'event_id' => $row['id'],
						),
						'disabled'         => ! current_user_can( \Gravity_Forms\Gravity_SMTP\Users\Roles::VIEW_EMAIL_LOG_PREVIEW ),
					),
				),
				array(
					'component' => 'Button',
					'props'     => array(
						'action'           => 'resend',
						'customAttributes' => array(
							'title' => esc_html__( 'Resend email', 'gravitysmtp' ),
						),
						'customClasses'    => array( 'gravitysmtp-data-grid__action' ),
						'icon'             => 'paper-plane',
						'iconPrefix'       => 'gravitysmtp-admin-icon',
						'spacing'          => [ 0, 2, 0, 0 ],
						'size'             => 'size-height-s',
						'type'             => 'icon-white',
						'data'             => array(
							'event_id' => $row['id'],
						),
						'disabled'         => ! current_user_can( \Gravity_Forms\Gravity_SMTP\Users\Roles::VIEW_EMAIL_LOG_PREVIEW ) || ! $row['can_resend'], // @todo: Add resend permission?
					),
				),
				array(
					'component' => 'Button',
					'props'     => array(
						'action'           => 'delete',
						'customAttributes' => array(
							'title' => esc_html__( 'Delete email log', 'gravitysmtp' ),
						),
						'customClasses'    => array( 'gravitysmtp-data-grid__action' ),
						'icon'             => 'trash',
						'iconPrefix'       => 'gravitysmtp-admin-icon',
						'size'             => 'size-height-s',
						'type'             => 'icon-white',
						'data'             => array(
							'event_id' => $row['id'],
						),
						'disabled'         => ! current_user_can( \Gravity_Forms\Gravity_SMTP\Users\Roles::DELETE_EMAIL_LOG ),
					),
				),
			),
		);

		return apply_filters( 'gravitysmtp_email_log_actions', $actions );
	}
}


$endpoint = new Get_Paginated_Log_Items();
$endpoint->run();
