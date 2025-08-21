<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutation_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Insert\Insert_Mutation_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Update\Update_Mutation_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Delete\Delete_Mutation_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Connect\Connect_Mutation_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Connect\Disconnect_Mutation_Token;

/**
 * Generic Mutation Token
 *
 * A token used to take a given Mutation string and determing the specific Mutatin type to execute.
 *
 * This is the top-level entry point when dealing with a Mutation.
 */
class Generic_Mutation_Token extends Mutation_Token {

	protected $type = 'generic_mutation';

	/**
	 * The type of this mutation.
	 *
	 * @var string
	 */
	protected $mutation_type;

	/**
	 * A specific Mutation_Token for the type of mutation being executed.
	 *
	 * @var Mutation_Token
	 */
	protected $typed_token;

	/**
	 * Public accessor for $mutation_type;
	 *
	 * @return string
	 */
	public function mutation_type() {
		return $this->mutation_type;
	}

	/**
	 * Public accessor for $typed_token.
	 *
	 * @return Mutation_Token
	 */
	public function mutation() {
		return $this->typed_token;
	}

	/**
	 * Return the $typed_token as children.
	 *
	 * @return Mutation_Token
	 */
	public function children() {
		return $this->typed_token;
	}

	/**
	 * Parse the string contents to values.
	 *
	 * @param string $contents
	 *
	 * @return void
	 */
	public function parse( $contents, $args = array() ) {
		preg_match_all( $this->get_parsing_regex(), $contents, $parts );
		$matches     = $parts[0];
		$marks       = $parts['MARK'];
		$typed_token = false;

		while ( ! empty( $matches ) ) {
			$value     = array_shift( $matches );
			$mark_type = array_shift( $marks );

			if ( $mark_type !== 'operation' ) {
				continue;
			}

			$cleaned = trim( $value, '{ (' );

			if ( strpos( $cleaned, 'insert_' ) !== false ) {
				$typed_token         = new Insert_Mutation_Token( $contents );
				$this->mutation_type = 'insert';
				continue;
			}

			if ( strpos( $cleaned, 'update_' ) !== false ) {
				$typed_token         = new Update_Mutation_Token( $contents );
				$this->mutation_type = 'update';
				continue;
			}

			if ( strpos( $cleaned, 'delete_' ) !== false ) {
				$typed_token         = new Delete_Mutation_Token( $contents );
				$this->mutation_type = 'delete';
				continue;
			}

			if ( strpos( $cleaned, 'disconnect_' ) !== false ) {
				$typed_token = new Disconnect_Mutation_Token( $contents );
				$this->mutation_type = 'disconnect';
				continue;
			}

			if ( strpos( $cleaned, 'connect_' ) !== false ) {
				$typed_token         = new Connect_Mutation_Token( $contents );
				$this->mutation_type = 'connect';
				continue;
			}

			$typed_token         = apply_filters( 'gravitytools_hermes_mutation_type_token', $typed_token, $cleaned, $contents );
			$this->mutation_type = apply_filters( 'gravitytools_hermes_mutation_type', false, $typed_token );
		}

		if ( empty( $typed_token ) ) {
			throw new \InvalidArgumentException( 'Invalid operation type passed to mutation.', 475 );
		}

		$this->typed_token = $typed_token;
	}

	/**
	 * The regex types to use while parsing.
	 *
	 * $key represents the MARK type, while the $value represents the REGEX string to use.
	 *
	 * @return string[]
	 */
	public function regex_types() {
		return array(
			'operation' => '\{[^\(]+\(',
		);
	}

}
