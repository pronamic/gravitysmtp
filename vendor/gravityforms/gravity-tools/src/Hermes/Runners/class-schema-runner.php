<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Runners;

use Gravity_Forms\Gravity_Tools\Hermes\Models\Model;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Data_Object_From_Array_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Field_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Utils\Model_Collection;

class Schema_Runner {

	/**
	 * @var Model_Collection
	 */
	protected $models;

	private $available_fields = array(
		'name',
		'fields',
		'metaFields',
		'relationships',
	);

	private $sub_fields_map = array(
		'fields'        => array(
			'name',
			'type',
		),
		'meta_fields'   => array(
			'name',
			'type',
		),
		'relationships' => array(
			'to',
			'accessCap',
		),
	);

	public function __construct( $models ) {
		$this->models = $models;
	}

	/**
	 * Run the process for gathering info about the schema.
	 *
	 * @var Data_Object_From_Array_Token $object
	 *
	 * @return array
	 */
	public function run( $object ) {
		$data = array();
		foreach ( $this->models->all() as $model ) {
			$data[] = $this->get_data_for_model( $object->children(), $model );
		}

		return $data;
	}

	/**
	 * Get model data for a single model using the given fields.
	 *
	 * @param array $fields
	 * @param Model $model
	 *
	 * @return array
	 */
	private function get_data_for_model( $fields, $model ) {
		$data = array();

		foreach ( $fields as $field ) {
			$check_val = is_a( $field, Field_Token::class ) ? $field->name() : $field->object_type();

			if ( ! in_array( $check_val, $this->available_fields ) ) {
				throw new \InvalidArgumentException( sprintf( 'Attempting to access invalid schema field %s', $check_val ) );
			}
			
			if ( is_a( $field, Data_Object_From_Array_Token::class ) ) {
				$subfields_to_include = $this->get_field_names_from_token( $field );
			}

			switch ( $check_val ) {
				case 'name':
					$data[ $check_val ] = $model->type();
					break;
				case 'fields':
					$row_data = array();
					foreach( $model->fields() as $field_name => $field_type ) {
						if ( ! is_string( $field_type ) ) {
							$field_type = 'custom';
						}
						$row_subdata = array();
						if ( in_array( 'name', $subfields_to_include ) ) {
							$row_subdata['name'] = $field_name; 
						}
						if ( in_array( 'type', $subfields_to_include ) ) {
							$row_subdata['type'] = $field_type;
						}
						$row_data[] = $row_subdata;
					}
					$data[ $check_val ] = $row_data;
					break;
				case 'metaFields':
					$row_data = array();
					foreach( $model->meta_fields() as $field_name => $field_type ) {
						if( ! is_string( $field_type ) ) {
							$field_type = 'custom';
						}
						$row_subdata = array();
						if ( in_array( 'name', $subfields_to_include ) ) {
							$row_subdata['name'] = $field_name; 
						}
						if ( in_array( 'type', $subfields_to_include ) ) {
							$row_subdata['type'] = $field_type;
						}

						$row_data[] = $row_subdata;
					}
					$data[ $check_val ] = $row_data;
					break;
				case 'relationships':
					$row_data = array();
					foreach( $model->relationships()->all() as $relationship ) {
						$row_subdata = array();
						if ( in_array( 'to', $subfields_to_include ) ) {
							$row_subdata['to'] = $relationship->to();
						}
						if( in_array( 'accessCap', $subfields_to_include ) ) {
							$row_subdata['accessCap'] = $relationship->cap();
						}
						$row_data[] = $row_subdata;
					}
					$data[ $check_val ] = $row_data;
					break;
				default:
					break;
			}
		}

		return $data;
	}

	/**
	 * Get individual field names from a given token.
	 *
	 * @param Data_Object_From_Array_Token $token
	 *
	 * @return array
	 */
	private function get_field_names_from_token( $token ) {
		$fields = array();

		foreach( $token->fields() as $field ) {
			$fields[] = $field->name();
		}

		return $fields;
	}
}
