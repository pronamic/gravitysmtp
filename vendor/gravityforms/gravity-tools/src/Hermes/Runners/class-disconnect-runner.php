<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Runners;

use Gravity_Forms\Gravity_Tools\Hermes\Models\Model;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Connect\Connect_Mutation_Token;

/**
 * A concrete implementation of Runner which handles database operations disconnecting
 * two object types from one another via a lookup table.
 */
class Disconnect_Runner extends Runner {

	/**
	 * Using the data provided by the Mutation_Token, this determines the correct
	 * DB table for the connection and inserts the appropriate IDs into the table.
	 *
	 * This also checks permissions for both object types to ensure that the user has
	 * the appropriate capabilities for running this connect mutation.
	 *
	 * @param Connect_Mutation_Token $mutation
	 * @param Model                  $object_model
	 *
	 * @return void
	 */
	public function run( $mutation, $object_model, $return = false ) {
		$from_object = $mutation->from_object();
		$to_object   = $mutation->to_object();
		$pairs       = $mutation->pairs();

		foreach ( $pairs as $pair ) {
			$to_id   = $pair['to'];
			$from_id = $pair['from'];

			$this->run_single( $from_object, $to_object, $from_id, $to_id );
		}

		$response = sprintf( '%s connections from %s to %s removed.', count( $pairs ), $from_object, $to_object );

		if ( $return ) {
			return $response;
		}

		wp_send_json_success( $response );
	}

	public function run_single( $from_object, $to_object, $from_id, $to_id ) {
		global $wpdb;

		$table_name = sprintf( '%s%s_%s_%s', $wpdb->prefix, $this->db_namespace, $from_object, $to_object );

		$disconnect_sql = sprintf( 'DELETE FROM %s WHERE %s_id = "%s" AND %s_id = "%s"', $table_name, $from_object, $from_id, $to_object, $to_id );

		$wpdb->query( $disconnect_sql );
	}
}
