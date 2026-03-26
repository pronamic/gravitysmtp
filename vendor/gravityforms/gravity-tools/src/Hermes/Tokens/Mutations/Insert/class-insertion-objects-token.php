<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Insert;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Token;

/**
 * Insertion Objects Token
 *
 * A token holding a series of Insertion Object Tokens containing the various items to insert during this mutation.
 */
class Insertion_Objects_Token extends Token {

	protected $type = 'insertion_objects';

	/**
	 * @var Insertion_Object_Token[]
	 */
	protected $objects;

	/**
	 * Return the array of Insertion Object Tokens as children.
	 *
	 * @return Insertion_Object_Token[]
	 */
	public function children() {
		return $this->objects;
	}

	/**
	 * Parse the string contents to values.
	 *
	 * @param string $contents
	 *
	 * @return void
	 */
	public function parse( $contents, $args = array() ) {
		$contents = preg_replace( "/\r|\n|\t/", '', $contents );
		preg_match_all( $this->get_parsing_regex(), $contents, $parts );

		$matches                                = $parts[0];
		$marks                                  = $parts['MARK'];
		$current_object_type                    = $args['object_type'];
		$organized_data[ $current_object_type ] = $this->recursively_organize_tokens( $matches, $marks, $current_object_type );
		$organized_objects                      = array();
		$this->recursively_convert_tokens_to_objects( $organized_objects, $organized_data[ $current_object_type ]['objects'], $current_object_type, false, false );

		$this->objects = $organized_objects;
	}

	private function recursively_organize_tokens( &$matches, &$marks, $object_type ) {
		$data              = array();
		$current_field_key = false;
		$is_child          = false;
		$is_array_value = false;

		while ( ! empty( $matches ) ) {
			$value     = array_shift( $matches );
			$mark_type = array_shift( $marks );

			switch ( $mark_type ) {
				case 'closingArray':
					if ( $is_array_value ) {
						$is_array_value = false;
					}
					break;
				case 'key':
					if ( $is_array_value ) {
						$data[ $current_field_key ][] = $value;
						break;
					}

					if ( $marks[0] === 'openingArray' ) {
						$object_type = $value;

						if ( $marks[1] !== 'openingObject' ) {
							$is_array_value    = true;
							$current_field_key = $value;
						}

						if ( ! isset( $data[ $value ] ) ) {
							$data[ $value ] = array();

							if ( $marks[1] === 'openingObject' && $object_type !== 'objects' ) {
								$data[ $value ]['isChild'] = true;
							}

							$is_child = ( $marks[1] === 'openingObject' );
						}
						break;
					}
					$current_field_key = $value;
					break;
				case 'openingObject':
					if ( $is_child ) {
						$data[ $object_type ][] = $this->recursively_organize_tokens( $matches, $marks, $object_type );
						break;
					}
					break;
				case 'closingObject':
					return $data;
				case 'value':
					$field_value = trim( $value, ': ' );
					if ( $current_field_key ) {
						$data[ $current_field_key ] = $field_value;
						$current_field_key          = false;
					}
					break;
				default:
					break;
			}
		}

		return $data;
	}

	private function recursively_convert_tokens_to_objects( &$organized_objects, $data, $current_object_type, $parent_object_type = false, $is_child = false ) {
		$fields  = array();
		$marks   = array();
		$matches = array();

		foreach ( $data as $object_idx => $object_data ) {
			// Avoid bad data.
			if ( ! is_array( $object_data ) || empty( $object_data ) ) {
				continue;
			}
			foreach ( $object_data as $key => $value ) {
				if ( is_array( $value ) && isset( $value['isChild'] ) ) {
					unset( $value['isChild'] );
					$this->recursively_convert_tokens_to_objects( $organized_objects, $value, $key, $current_object_type, true );
					continue;
				}

				$field_key            = $key;
				$fields[ $field_key ] = is_string( $value ) ? trim( $value, ': "' ) : $value;
			}

			$organized_objects[] = new Insertion_Object_Token(
				$marks,
				$matches,
				array(
					'object_type'        => $current_object_type,
					'fields'             => $fields,
					'is_child'           => $is_child,
					'parent_object_type' => $parent_object_type,
				)
			);
		}
	}

	/**
	 * The regex types to use while parsing.
	 *
	 * $key represents the MARK type, while the $value represents the REGEX string to use.
	 *
	 * @return string[]
	 */
	public function regex_types() {
		// (?|(*MARK:openingArray)\[|(*MARK:openingObject)\{|(*MARK:closingObject)\}|(*MARK:closingArray)\]|(*MARK:value):\s*"[^"]+\s*"|(*MARK:key)[a-zA-Z0-9_\-]+)
		return array(
			'openingArray'  => '\[',
			'openingObject' => '\{',
			'closingObject' => '\}',
			'closingArray'  => '\]',
			'value'         => ':\s*"[^"]+\s*"',
			'key'           => '[a-zA-Z0-9_\-]+',
		);
	}
}
