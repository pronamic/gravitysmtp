<?php

namespace Gravity_Forms\Gravity_SMTP\Environment\Endpoints;

use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Uninstall_Endpoint extends Endpoint {

	const ACTION_NAME = 'gravitysmtp_uninstall_plugin';

	protected $minimum_cap = Roles::EDIT_UNINSTALL;

	public function handle() {
		if ( ! current_user_can( Roles::EDIT_UNINSTALL ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'gravitysmtp' ), 403 );
		}

		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$this->delete_options();
		$this->delete_tables();
		$this->deactivate_plugin();

		wp_send_json_success( __( 'Gravity SMTP successfully uninstalled.', 'gravitysmtp' ) );
	}

	public function get_nonce_name() {
		return self::ACTION_NAME;
	}

	private function delete_options() {
		global $wpdb;
		$query = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%%%s%%'", 'gravitysmtp_' );
		$wpdb->query( $query );

		$query = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%%%s%%'", 'gsmtp_' );
		$wpdb->query( $query );
	}

	private function delete_tables() {
		global $wpdb;
		$query = "DROP TABLE IF EXISTS {$wpdb->prefix}gravitysmtp_events, {$wpdb->prefix}gravitysmtp_event_logs";
		$wpdb->query( $query );
	}

	private function deactivate_plugin() {
		deactivate_plugins( '/gravitysmtp/gravitysmtp.php' );
	}

}
