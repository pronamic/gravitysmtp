<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Runners;

use Gravity_Forms\Gravity_Tools\Hermes\Models\Model;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Connect\Connect_Mutation_Token;

/**
 * A concrete implementation of Runner which handles database operations connecting
 * two object types to one another via a lookup table.
 */
class Connect_Runner extends Runner {

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
		global $wpdb;

		$from_object = $mutation->from_object();
		$to_object   = $mutation->to_object();
		$pairs       = $mutation->pairs();

		foreach ( $pairs as $pair ) {
			$to_id   = $pair['to'];
			$from_id = $pair['from'];

			$this->run_single( $from_object, $to_object, $from_id, $to_id, $object_model );
		}

		$response = sprintf( '%s connections from %s to %s created.', count( $pairs ), $from_object, $to_object );

		if ( $return ) {
			return $response;
		}

		wp_send_json_success( $response );
	}

	public function run_single( $from_object, $to_object, $from_id, $to_id, $object_model ) {
		global $wpdb;

		if ( ! $object_model->relationships()->has( $to_object ) ) {
			$error_message = sprintf( 'Relationship from %s to %s does not exist.', $from_object, $to_object );
			throw new \InvalidArgumentException( $error_message, 455 );
		}

		if ( ! $object_model->relationships()->get( $to_object )->has_access() ) {
			$error_message = sprintf( 'Attempting to access forbidden object type %s.', $to_object );
			throw new \InvalidArgumentException( $error_message, 403 );
		}

		$table_name = sprintf( '%s%s_%s_%s', $wpdb->prefix, $this->db_namespace, $from_object, $to_object );

		$check_sql = sprintf( 'SELECT * FROM %s WHERE %s_id = "%s" AND %s_id = "%s"', $table_name, $from_object, $from_id, $to_object, $to_id );

		$existing = $wpdb->get_results( $check_sql );

		if (  empty( $existing ) ) {
		  $connect_sql = sprintf( 'INSERT INTO %s ( %s_id, %s_id ) VALUES( "%s", "%s" )', $table_name, $from_object, $to_object, $from_id, $to_id );
		}

		$wpdb->query( $connect_sql );
	}
}
