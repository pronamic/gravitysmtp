<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Delete;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutation_Token;

/**
 * Delete Mutation Token
 *
 * Contains the values for executing a Delete mutation.
 */
class Delete_Mutation_Token extends Mutation_Token {

	protected $operation = 'delete';

	/**
	 * Token holding the ID to delete.
	 *
	 * @var ID_To_Delete_Token
	 */
	protected $id_to_delete;

	/**
	 * Public accessor for the ID to delete.
	 *
	 * @return string
	 */
	public function ids_to_delete() {
		return $this->id_to_delete->ids();
	}

	/**
	 * Pass through the children from the $id_to_delete token.
	 *
	 * @return array
	 */
	public function children() {
		return $this->id_to_delete->children();
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
		$data    = array();

		while ( ! empty( $matches ) ) {
			$value     = array_shift( $matches );
			$mark_type = array_shift( $marks );

			switch ( $mark_type ) {
				case 'operation_alias':
					$data['object_type'] = str_replace( 'delete_', '', $value );
					$data['alias']       = $value;
					break;
				case 'arg_group':
					$data['id_to_delete'] = new ID_To_Delete_Token( $value );
					break;
				case 'alias':
					$has_alias = $value;
					break;
			}
		}

		if ( empty( $data['id_to_delete'] ) || empty( $data['object_type'] ) ) {
			throw new \InvalidArgumentException( 'Delete payload malformed. Check values and try again.', 490 );
		}

		$this->set_properties( $data );
	}

	protected function set_properties( $data ) {
		$this->alias        = $data['alias'];
		$this->object_type  = $data['object_type'];
		$this->id_to_delete = $data['id_to_delete'];
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
			'operation_alias' => 'delete_[^\(]*',
			'arg_group'       => '\([^\)]+\)',
			'alias'           => '[_A-Za-z][_0-9A-Za-z]*:',
			'open_bracket'    => '{',
			'close_bracket'   => '}',
		);
	}

}
