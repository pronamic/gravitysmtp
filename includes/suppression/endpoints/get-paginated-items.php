<?php

ini_set( 'html_errors', 0 );
define( 'SHORTINIT', true );

require '../../utils/class-fast-endpoint.php';

class Get_Paginated_Suppression_Items extends \Gravity_Forms\Gravity_SMTP\Utils\Fast_Endpoint {

	public function run() {
		check_ajax_referer( 'suppressed_emails_page', 'security' );

		$per_page       = filter_input( INPUT_POST, 'per_page', FILTER_SANITIZE_NUMBER_INT );
		$requested_page = filter_input( INPUT_POST, 'requested_page', FILTER_SANITIZE_NUMBER_INT );
		$search_term    = filter_input( INPUT_POST, 'search_term' );

		if ( ! empty( $search_term ) ) {
			$search_term = htmlspecialchars( $search_term );
		}

		$requested_page = intval( $requested_page );
		$offset         = ( $requested_page - 1 ) * $per_page;

		if ( empty( $per_page ) ) {
			$per_page = 20;
		}

		$suppression_model = new \Gravity_Forms\Gravity_SMTP\Models\Suppressed_Emails_Model();
		$rows              = $suppression_model->paginate( $requested_page, $per_page, $search_term );
		$count             = $suppression_model->count( $search_term );

		$data = array(
			'rows'      => $this->get_suppression_data_formatted_as_rows( $rows ),
			'total'     => $count,
			'row_count' => count( $rows ),
		);

		wp_send_json_success( $data );
	}

	protected function extra_includes() {
		return array(
			'../../users/class-roles.php',
			'../../enums/class-suppression-reason-enum.php',
			'../../models/class-suppressed-emails-model.php',
		);
	}

	private function get_suppression_data_formatted_as_rows( $data ) {
		$suppression_model = new \Gravity_Forms\Gravity_SMTP\Models\Suppressed_Emails_Model();

		return $suppression_model->format_as_data_rows( $data );
	}

}

$endpoint = new Get_Paginated_Suppression_Items();
$endpoint->run();