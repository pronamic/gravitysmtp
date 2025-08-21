<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Runners;

use Gravity_Forms\Gravity_Tools\Hermes\Models\Model;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Update\Update_Mutation_Token;

/**
 * A concrete implementation of Runner which handles database operations required
 * to insert a set of objects into the appropriate tables.
 */
class Update_Runner extends Runner {

	/**
	 * Using the data provided by the Mutation_Token, this determines the correct
	 * DB table for the update and updates the record identified by the ID column.
	 *
	 * This also checks permissions for the object type to ensure that the user has
	 * the appropriate capabilities for running this update mutation. If any meta fields
	 * are defined in the mutation, those values are inserted into the meta table with
	 * the appropriate values.
	 *
	 * @param Update_Mutation_Token $mutation
	 * @param Model                 $object_model
	 *
	 * @return void
	 */
	public function run( $mutation, $object_model, $return = false ) {
		$fields_to_update = $mutation->children()->children();

		if ( ! array_key_exists( 'id', $fields_to_update ) ) {
			$error_string = sprintf( 'Update mutations must contain an id in the fields list. Fields provided: %s', json_encode( array_keys( $fields_to_update ) ) );
			throw new \InvalidArgumentException( $error_string, 452 );
		}

		$object_id = $fields_to_update['id'];

		$categorized_fields = $this->categorize_fields( $object_model, $fields_to_update );

		// Set dateUpdated with current time
		$categorized_fields['local']['dateUpdated'] = gmdate( 'Y-m-d H:i:s', time() );

		$this->handle_single_update( $object_model, $categorized_fields, $object_id );

		$objects_gql = sprintf( '{ %s: %s(id: %s){ %s }', $object_model->type(), $object_model->type(), $object_id, implode( ', ', $mutation->return_fields() ) );

		$data = $this->query_handler->handle_query( $objects_gql );

		if ( $return ) {
			return $data;
		}

		wp_send_json_success( $data );
	}

	/**
	 * Helper method to handle an individual update action from the array of objects to update.
	 *
	 * @param Model $object_model
	 * @param array $categorized_fields
	 *
	 * @return int
	 */
	private function handle_single_update( $object_model, $categorized_fields, $object_id ) {
		global $wpdb;

		$table_name = sprintf( '%s%s_%s', $wpdb->prefix, $this->db_namespace, $object_model->type() );
		$field_list = $this->get_update_field_list( $categorized_fields['local'] );
		$sql        = sprintf( 'UPDATE %s SET %s WHERE id = "%s"', $table_name, $field_list, $object_id );

		$wpdb->query( $sql );

		if ( ! empty( $categorized_fields['meta'] ) ) {
			foreach ( $categorized_fields['meta'] as $key => $value ) {
				$meta_table_name = sprintf( '%s%s_meta', $wpdb->prefix, $this->db_namespace );

				$delete_sql = sprintf( 'DELETE FROM %s WHERE meta_name = "%s" AND object_id = "%s"', $meta_table_name, $key, $object_id );
				$wpdb->query( $delete_sql );

				$insert_fields_string = 'object_type, object_id, meta_name, meta_value';
				$insert_values_string = sprintf( '"%s", "%s", "%s", "%s"', $object_model->type(), $object_id, $key, $value );
				$meta_sql             = sprintf( 'INSERT INTO %s (%s) VALUES (%s)', $meta_table_name, $insert_fields_string, $insert_values_string );

				$wpdb->query( $meta_sql );
			}
		}

		return $object_id;
	}

}
