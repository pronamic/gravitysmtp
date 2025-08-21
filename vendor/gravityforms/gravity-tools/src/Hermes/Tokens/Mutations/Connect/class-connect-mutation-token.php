<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Connect;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutation_Token;

/**
 * Connect Mutation Token
 *
 * A token containing information for perfoming a Connect Mutation.
 */
class Connect_Mutation_Token extends Mutation_Token {

	protected $operation = 'connect';

	/**
	 * A token containing the IDs to connect.
	 *
	 * @var Connection_Values_Token
	 */
	protected $connection_ids;

	/**
	 * The object type to connect from.
	 *
	 * @var string
	 */
	protected $from_object;

	/**
	 * The object type to connect to.
	 *
	 * @var string
	 */
	protected $to_object;

	/**
	 * Public $from_object accessor.
	 *
	 * @return string
	 */
	public function from_object() {
		return $this->from_object;
	}

	/**
	 * Public $to_object accessor.
	 *
	 * @return string
	 */
	public function to_object() {
		return $this->to_object;
	}

	/**
	 * Public $pairs accessor.
	 *
	 * $return array
	 */
	public function pairs() {
		return $this->connection_ids->pairs();
	}

	/**
	 * Return the class properties as an array when children() is called.
	 *
	 * @return array
	 */
	public function children() {
		return array(
			'from_object' => $this->from_object(),
			'to_object'   => $this->to_object(),
			'pairs'       => $this->pairs(),
		);
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
					$objects      = str_replace( 'connect_', '', $value );
					$object_parts = explode( '_', $objects );

					if ( count( $object_parts ) !== 2 ) {
						throw new \InvalidArgumentException( 'Error parsing connection object types.', 480 );
					}

					$data['from_object'] = $object_parts[0];
					$data['to_object']   = $object_parts[1];
					$data['alias']       = $value;
					break;
				case 'arg_group':
					$data['connection_ids'] = new Connection_Values_Token( $value );
					break;
				case 'alias':
					$has_alias = $value;
					break;
			}
		}

		if ( empty( $data['connection_ids'] ) || empty( $data['from_object'] ) ) {
			throw new \InvalidArgumentException( 'Connect payload malformed. Check values and try again.', 485 );
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
		$this->alias          = $data['alias'];
		$this->connection_ids = $data['connection_ids'];
		$this->object_type    = $data['from_object'];
		$this->from_object    = $data['from_object'];
		$this->to_object      = $data['to_object'];
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
			'operation_alias' => 'connect_[^\(]*',
			'arg_group'       => '\[[^\]]+\]',
			'alias'           => '[_A-Za-z][_0-9A-Za-z]*:',
			'open_bracket'    => '{',
			'close_bracket'   => '}',
		);
	}
}

