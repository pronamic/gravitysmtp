<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Runners;

use Gravity_Forms\Gravity_Tools\Hermes\Models\Model;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Delete\Delete_Mutation_Token;

/**
 * A concrete implementation of Runner which handles database operations for
 * deleting an object from the appropriate tables.
 */
class Delete_Runner extends Runner {

	/**
	 * Using the data provided by the Mutation_Token, this determines the correct
	 * DB table for the deletion action and removes the record.
	 *
	 *
	 * @param Delete_Mutation_Token $mutation
	 * @param Model                  $object_model
	 *
	 * @return void
	 */
	public function run( $mutation, $object_model, $return = false ) {
		global $wpdb;

		$ids_to_delete = $mutation->ids_to_delete();
		$ids_to_delete = array_map( function( $id ) {
			return esc_sql( $id );
		}, $ids_to_delete );

		$table_name   = sprintf( '%s%s_%s', $wpdb->prefix, $this->db_namespace, $object_model->type() );

		if ( in_array( 'all', $ids_to_delete ) ) {
			$delete_sql = sprintf( 'DELETE FROM %s', $table_name );
			$ids_to_delete = array( 'all' );
		} else {
			$in_clause = sprintf( '(%s)', implode( ', ', $ids_to_delete ) );
			$delete_sql   = sprintf( 'DELETE FROM %s WHERE id IN %s', $table_name, $in_clause );
		}

		$wpdb->query( $delete_sql );

		if( $return ) {
			return $ids_to_delete;
		}

		wp_send_json_success( array( 'deleted_ids' => $ids_to_delete ) );
	}

}
