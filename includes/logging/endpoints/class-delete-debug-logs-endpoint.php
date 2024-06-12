<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Endpoints;

use Gravity_Forms\Gravity_SMTP\Models\Debug_Log_Model;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Delete_Debug_Logs_Endpoint extends Endpoint {

//	const PARAM_LOG_IDS  = 'log_ids';
	const PARAM_ALL_LOGS = 'all_logs';
//	const PARAM_MAX_DATE = 'max_date';

	const ACTION_NAME = 'delete_debug_logs';

	/**
	 * @var Debug_Log_Model
	 */
	protected $logs;

	protected $required_params = array(
//		self::PARAM_LOG_IDS,
		self::PARAM_ALL_LOGS,
	);

	public function __construct( Debug_Log_Model $logs ) {
		$this->logs = $logs;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$delete_all_logs = filter_input( INPUT_POST, self::PARAM_ALL_LOGS );
		$delete_all_logs = htmlspecialchars( $delete_all_logs );
//		$max_date        = filter_input( INPUT_POST, self::PARAM_MAX_DATE );
//
//		if ( ! empty( $max_date ) ) {
//			$max_date = htmlspecialchars( $max_date );
//		}
//
//		if ( ! empty( $max_date ) && $delete_all_logs == '1' ) {
//			$this->logs->delete_before( $max_date );
//			wp_send_json_success( array( 'message' => __( 'Logs deleted successfully.', 'gravitysmtp' ) ), 200 );
//		}

		if ( $delete_all_logs == '1' ) {
			$this->logs->clear();
			wp_send_json_success( array( 'message' => __( 'All logs deleted successfully', 'gravitysmtp' ) ), 200 );
		}

//		$log_ids = filter_input( INPUT_POST, self::PARAM_LOG_IDS, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
//
//		foreach( $log_ids as $id ) {
//			$id_to_delete = filter_var( $id, FILTER_SANITIZE_NUMBER_INT );
//			$this->logs->delete( $id_to_delete );
//		}
//
//		wp_send_json_success( array( 'message' => __( $log_ids, 'gravitysmtp' ) ), 200 );
	}

}