<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Update;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutation_Token;

/**
 * Update Mutation Token
 *
 * Contains the values needed for executing an Update Mutation.
 */
class Update_Mutation_Token extends Mutation_Token {

	protected $operation = 'update';

	/**
	 * The fields to return after the Update has been performed.
	 *
	 * @var array
	 */
	protected $return_fields = array();

	/**
	 * The fields to update during the mutation.
	 *
	 * @var Fields_To_Update_Token
	 */
	protected $fields_to_update;

	/**
	 * Public accessor for $return_fields
	 *
	 * @return array
	 */
	public function return_fields() {
		return $this->return_fields;
	}

	/**
	 * Public acessor for $fields_to_update.
	 *
	 * @return Fields_To_Update_Token
	 */
	public function fields_to_update() {
		return $this->fields_to_update;
	}

	/**
	 * Return $fields_to_update as children.
	 *
	 * @return Fields_To_Update_Token
	 */
	public function children() {
		return $this->fields_to_update;
	}

	/**
	 * Parse the string contents to values.
	 *
	 * @param string $contents
	 *
	 * @return void
	 */
	public function parse( $contents, $args = array() ) {
		preg_match_all( $this->get_parsing_regex(), $contents, $results );
		$this->tokenize( $results );

		return;
	}

	/**
	 * Use the given REGEX matches to generate the appropriate tokens for this mutation.
	 *
	 * @param array $parts - An array resulting from preg_match_all() with this class's regex values.
	 *
	 * @return void
	 */
	public function tokenize( $parts ) {
		$matches = $parts[0];
		$marks   = $parts['MARK'];
		$data    = array(
			'return_fields' => array(),
		);

		$next_is_return = false;

		while ( ! empty( $matches ) ) {
			$value     = array_shift( $matches );
			$mark_type = array_shift( $marks );

			switch ( $mark_type ) {
				case 'returning_def':
					$next_is_return = true;
					break;
				case 'operation_alias':
					$data['object_type'] = str_replace( 'update_', '', $value );
					$data['alias']       = $value;
					break;
				case 'arg_group':
					$data['fields_to_update'] = new Fields_To_Update_Token( $value );
					break;
				case 'alias':
					$has_alias = $value;
					break;
				case 'identifier':
					if ( ! $next_is_return ) {
						break;
					}

					$data['return_fields'][] = $value;
					break;
				case 'close_bracket':
					if ( $next_is_return ) {
						$next_is_return = false;
						break;
					} else {
						$this->set_properties( $data );

						return;
					}
			}
		}

		$this->set_properties( $data );
	}

	/**
	 * Use the parsed tokens data to set the class properties.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	protected function set_properties( $data ) {
		$this->alias            = $data['alias'];
		$this->object_type      = $data['object_type'];
		$this->fields_to_update = $data['fields_to_update'];
		$this->return_fields    = $data['return_fields'];
	}

	/**
	 * The regex types to use while parsing.
	 *
	 * $key represents the MARK type, while the $value represents the REGEX string to use.
	 *
	 * @return string[]
	 */
	protected function regex_types() {
		return array(
			'returning_def'   => 'returning',
			'operation_alias' => 'update_[^\(]*',
			'arg_group'       => '\([^\)]+\)',
			'alias'           => '[_A-Za-z][_0-9A-Za-z]*:',
			'identifier'      => '[_A-Za-z][_0-9A-Za-z]*',
			'open_bracket'    => '{',
			'close_bracket'   => '}',
		);
	}

}
