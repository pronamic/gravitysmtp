<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Scheduling;

use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Models\Log_Details_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;

class Handler {

	/**
	 * @var Data_Store_Router
	 */
	protected $plugin_data;

	/**
	 * @var Event_Model
	 */
	protected $emails;

	/**
	 * @var Log_Details_Model
	 */
	protected $logs;

	/**
	 * @var Debug_Logger|null
	 */
	protected $debug_logger;

	public function __construct( $plugin_data_store, $email_model, $log_model, $debug_logger = null ) {
		$this->plugin_data  = $plugin_data_store;
		$this->emails       = $email_model;
		$this->logs         = $log_model;
		$this->debug_logger = $debug_logger;
	}

	/**
	 * Run the log retention functions. Currently called by a cron job.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function run_log_retention() {
		// Fall back to the same default the Settings > Logging screen shows, so the cron
		// honors it even before a user has ever saved this setting.
		$retention   = $this->plugin_data->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_RETENTION, Save_Plugin_Settings_Endpoint::DEFAULT_EVENT_LOG_RETENTION );
		$max_records = $this->plugin_data->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_MAX_EVENT_RECORDS, 0 );

		// Retention is set to never delete, or we have a missing value, Bail.
		if ( ! Booliesh::get( $retention ) && ! Booliesh::get( $max_records ) ) {
			return;
		}

		$result = $this->delete_expired_items( $this->get_retention_cutoff( $retention, $max_records ) );

		if ( is_wp_error( $result ) && $this->debug_logger ) {
			$this->debug_logger->log_error( __METHOD__ . '(): ' . implode( ' ', $result->get_error_messages() ) );
		}
	}

	/**
	 * Get the retention cutoff: records at or before it are expired.
	 *
	 * @since 1.0
	 *
	 * @param int $retention
	 * @param int $max_records
	 *
	 * @return array|null An array with a (GMT) `date_created` key and an `id` key, or null when nothing has expired.
	 *                    A null `id` means every record at or before `date_created` is expired; otherwise records
	 *                    sharing that `date_created` are only expired up to and including that id.
	 */
	private function get_retention_cutoff( $retention, $max_records ) {
		if ( Booliesh::get( $max_records ) ) {
			return $this->emails->get_retention_cutoff( $max_records );
		}

		$interval_string = sprintf( ' - %d days', $retention );

		return array(
			'date_created' => gmdate( 'Y-m-d H:i:s', strtotime( $interval_string ) ),
			'id'           => null,
		);
	}

	/**
	 * Delete expired items, and the log rows that belonged to them, in bounded-size
	 * chunks so retention completes quickly and with flat memory use regardless of
	 * log size.
	 *
	 * @since 1.0
	 *
	 * @param array|null $cutoff The cutoff from get_retention_cutoff(), or null when nothing has expired.
	 *
	 * @return true|\WP_Error
	 */
	private function delete_expired_items( $cutoff ) {
		global $wpdb;

		$errors = new \WP_Error();

		if ( $cutoff !== null && ! $this->emails->delete_before_in_chunks( $cutoff['date_created'], $cutoff['id'] ) ) {
			$errors->add( 'delete_events_failed', sprintf( 'Unable to delete expired email log events: %s', $wpdb->last_error ) );
		}

		// Always clean up orphaned log rows, even when no events expired on this run or
		// deleting them failed part-way, so that rows left behind by an interrupted run
		// (or by the events deleted before a failure) are still removed.
		if ( ! $this->logs->delete_orphaned_in_chunks() ) {
			$errors->add( 'delete_logs_failed', sprintf( 'Unable to delete orphaned email log details: %s', $wpdb->last_error ) );
		}

		return $errors->get_error_codes() ? $errors : true;
	}

}