<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens;

class Data_Object_From_Array_Token extends Token_From_Array {

	protected $type = 'Data_Object';

	protected $object_type;

	protected $fields;

	protected $arguments;

	protected $alias;

	public function object_type() {
		return $this->object_type;
	}

	public function arguments() {
		if ( ! empty( $this->arguments ) ) {
			return $this->arguments->items();
		}

		return array();
	}

	public function fields() {
		return $this->fields;
	}

	public function alias() {
		return $this->alias;
	}

	public function parse( &$matches, &$marks, $additional_args = array() ) {
		$data = array(
			'object_type' => $additional_args['object_type'],
			'alias'       => trim( $additional_args['alias'], ' :' ),
			'arguments'   => false,
			'fields'      => array(),
		);

		$has_alias = false;

		while ( ! empty( $matches ) ) {
			$value     = array_shift( $matches );
			$mark_type = array_shift( $marks );

			switch ( $mark_type ) {
				case 'arg_group':
					$data['arguments'] = new Arguments_Token( $value );
					$data['arguments']->set_parent( $this );
					break;
				case 'alias':
					$has_alias = $value;
					break;
				case 'identifier':
					if ( $marks[0] === 'open_bracket' || ( $marks[0] === 'arg_group' && $marks[1] === 'open_bracket' ) ) {
						$data_object = new self( $matches, $marks, array(
							'object_type' => $value,
							'alias'       => $has_alias ? $has_alias : $value,
						) );
						$data_object->set_parent( $this );
						$data['fields'][] = $data_object;
						$has_alias        = false;
						break;
					}

					$field_data = array(
						'name'  => $value,
						'alias' => trim( $has_alias, ' :' ),
					);

					if ( $marks[0] === 'arg_group' ) {
						$args                    = array_shift( $matches );
						$mark                    = array_shift( $marks );
						$field_data['arguments'] = new Arguments_Token( $args );
						$field_data['arguments']->set_parent( $this );
					}

					$field_token = new Field_Token( $matches, $marks, $field_data );
					$field_token->set_parent( $this );
					$data['fields'][] = $field_token;
					$has_alias        = false;
					break;
				case 'close_bracket':
					$this->set_properties( $data );

					return;
			}
		}

		$this->set_properties( $data );
	}

	private function set_properties( $data ) {
		$this->fields      = $data['fields'];
		$this->arguments   = $data['arguments'];
		$this->object_type = $data['object_type'];
		$this->alias       = $data['alias'];
	}

	public function children() {
		return $this->fields();
	}

}
