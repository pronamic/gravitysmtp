<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Runners;

use Gravity_Forms\Gravity_Tools\Hermes\Enum\Field_Type_Validation_Enum;
use Gravity_Forms\Gravity_Tools\Hermes\Models\Model;
use Gravity_Forms\Gravity_Tools\Hermes\Query_Handler;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutation_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Utils\Model_Collection;

/**
 * Runner
 *
 * This provides the abstract contract for a Runner.
 *
 * Runners are the classes responsible for actually running a given mutation type.
 * The majority of the logic already exists in this abstract class, so all a concrete
 * need do is implement the public ::run() method to take the mutation values and
 * handle the database interactions.
 */
abstract class Runner {

	/**
	 * The namespace to use for DB tables. This is used between the
	 * $wpdb->prefix value and the DB table name.
	 *
	 * Example: passing 'gravitytools' here would result in tables such
	 * as 'wp_gravitytools_meta', etc.
	 *
	 * This is typically defined a single time in a service provider and passed
	 * to the various classes which need it, such as this Runner.
	 *
	 * @var string
	 */
	protected $db_namespace;

	/**
	 * The Query Handler is used to form response objects for Insert and Update operations.
	 * Using it instead of direct SQL calls ensures that aliases, field validation, etc, are
	 * all applied to the response just like a normal Query would.
	 *
	 * @var Query_Handler
	 */
	protected $query_handler;

	/**
	 * The collection of models currently available to the system.
	 *
	 * @var Model_Collection
	 */
	protected $models;

	/**
	 * See property descriptions for more information about these arguments and their usage.
	 *
	 * @param string           $db_namespace
	 * @param Query_Handler    $query_handler
	 * @param Model_Collection $model_collection
	 *
	 * @return void
	 */
	public function __construct( $db_namespace, $query_handler, $model_collection ) {
		$this->db_namespace  = $db_namespace;
		$this->query_handler = $query_handler;
		$this->models        = $model_collection;
	}

	/**
	 * Run the actual mutation action against the database.
	 *
	 * Concrete Runners will implement this method in order to handle all
	 * of the database transactions for this mutation type.
	 *
	 * This method should have no return value, as it is intended to be the final
	 * process called before echoing the JSON to return to the client. As such, this method
	 * should always end with either wp_json_send_success() or wp_json_send_error() to ensure
	 * that values are echoed correctly and that the request doesn't continue after processing.
	 *
	 * @param Mutation_Token $mutation
	 * @param Model          $object_model
	 * @param bool           $return
	 *
	 * @return void
	 */
	abstract public function run( $mutation, $object_model, $return = false );

	/**
	 * Helper method to take an array of fields and return a comma-delimited
	 * list for use in INSERT column statements.
	 *
	 * @param array $fields
	 *
	 * @return string
	 */
	protected function get_field_name_list_from_fields( $fields ) {
		return implode( ', ', array_keys( $fields ) );
	}

	/**
	 * Helper method to take an array of fields and return a comma-delimited
	 * list of their values for use in an INSERT VALUES() statement.
	 *
	 * @param array $fields
	 *
	 * @return string
	 */
	protected function get_field_values_list_from_fields( $fields ) {
		$values = array_values( $fields );
		foreach ( $values as $key => $value ) {
			$values[ $key ] = sprintf( '"%s"', $value );
		}

		return implode( ', ', $values );
	}

	/**
	 * Helper method to take an array of fields and return a set of
	 * comma-delimited pairs of 'key = value' strings for usage in
	 * UPDATE statements.
	 *
	 * @param array $fields
	 *
	 * @return string
	 */
	protected function get_update_field_list( $fields ) {
		$pairs = array();

		foreach ( $fields as $key => $value ) {
			if ( $key === 'id' ) {
				continue;
			}
			$pairs[] = sprintf( '%s = "%s"', $key, $value );
		}

		return implode( ', ', $pairs );
	}

	/**
	 * Takes an object model and array of fields to process and categorizes them into
	 * 'local' (aka, existing in the database as columns) fields and 'meta' fields. This
	 * also uses the Model to check permissions for the given object type, and ensures
	 * that all referenced fields are properly defined in the Model.
	 *
	 * @param Model $object_model
	 * @param array $fields_to_process
	 *
	 * @return array|array[]
	 */
	protected function categorize_fields( $object_model, $fields_to_process ) {
		$categorized = array(
			'meta'  => array(),
			'local' => array(),
		);

		foreach ( $fields_to_process as $field_name => $value ) {
			if ( ! $object_model->supports_ad_hoc_fields() && ! array_key_exists( $field_name, $object_model->fields() ) && ! array_key_exists( $field_name, $object_model->meta_fields() ) ) {
				$error_string = sprintf( 'Attempting to access invalid field %s on object type %s', $field_name, $object_model->type() );
				throw new \InvalidArgumentException( $error_string, 450 );
			}

			if ( array_key_exists( $field_name, $object_model->fields() ) ) {
				$field_validation_type = $object_model->fields()[ $field_name ];
				$validated             = Field_Type_Validation_Enum::validate( $field_validation_type, $value );

				if ( ! is_null( $value ) && is_null( $validated ) ) {
					$field_type_string = is_string( $field_validation_type ) ? $field_validation_type : 'callback';
					$error_string      = sprintf( 'Invalid field value %s sent to field %s with a type of %s.', $value, $field_name, $field_type_string );
					throw new \InvalidArgumentException( $error_string, 451 );
				}

				$categorized['local'][ $field_name ] = $validated;
			}

			if ( array_key_exists( $field_name, $object_model->meta_fields() ) ) {
				$field_validation_type = $object_model->meta_fields()[ $field_name ];

				$validated = Field_Type_Validation_Enum::validate( $field_validation_type, $value );

				if ( ! is_null( $value ) && is_null( $validated ) ) {
					$error_string = sprintf( 'Invalid field value %s sent to field %s with a type of %s.', $value, $field_name, (string) $field_validation_type );
					throw new \InvalidArgumentException( $error_string, 451 );
				}

				$categorized['meta'][ $field_name ] = $validated;
			}
		}

		return $categorized;
	}
}

