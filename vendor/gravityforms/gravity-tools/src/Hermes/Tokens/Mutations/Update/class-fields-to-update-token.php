<?php

namespace Gravity_Forms\Gravity_CRM\Gravity_Tools\Hermes\Tokens\Mutations\Update;

use Gravity_Forms\Gravity_CRM\Gravity_Tools\Hermes\Tokens\Token;

/**
 * Fields to Update Token
 *
 * A token holding the fields to update during this Mutation.
 */
class Fields_To_Update_Token extends Token {

	protected $type = 'Fields_To_Update';

	/**
	 * The fields to update.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Public accessor for $items.
	 *
	 * @return array
	 */
	public function items() {
		return $this->items;
	}

	/**
	 * Return $items as children.
	 *
	 * @return array
	 */
	public function children() {
		return $this->items();
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
	}

	private function reset_state( &$state ) {
		$state = array(
		  'is_text_block' => false,
		  'key_found'     => '',
		  'value_found'   => '',
		);
	}

	protected function tokenize( $parts ) {
		$matches = $parts[0];
		$marks   = $parts['MARK'];
		$state   = array(
		  'is_text_block' => false,
		  'key_found'     => '',
		  'value_found'   => '',
		);
		$data    = array();

		while ( ! empty( $matches ) ) {
			$value     = array_shift( $matches );
			$mark_type = array_shift( $marks );

			switch ( $mark_type ) {
				case 'string':
					if ( ! $state['key_found'] && ! $state['is_text_block'] ) {
						$state['key_found'] = trim( $value, ': )' );
						break;
					}

					if ( $state['key_found'] && ! $state['is_text_block'] && ! $state['value_found'] ) {
						$state['value_found'] = trim( $value, ': )' );
						break;
					}

					if ( $state['is_text_block'] ) {
						$state['value_found'] = $state['value_found'] . $value;
					}
					break;

				case 'comma':
					if ( $state['is_text_block'] ) {
						$state['value_found'] = $state['value_found'] . $value;
						break;
					}

					// End of key/value pair
					$data[ $state['key_found'] ] = $state['value_found'];

					$this->reset_state( $state );
					break;

				case 'colon':
        case 'comma':
					if ( $state['is_text_block'] ) {
						$state['value_found'] = $state['value_found'] . $value;
						break;
					}
					break;

				case 'quote':
					// Start of text block.
					if ( ! $state['is_text_block'] ) {
						$state['is_text_block'] = true;
						break;
					}

					// End of text block.
					$data[ $state['key_found'] ] = $state['value_found'];

					$this->reset_state( $state );
					break;

				case 'num':
					if ( $state['key_found'] && ! $state['is_text_block'] ) {
						$state['value_found'] = $value;
						break;
					}

					if ( $state['is_text_block'] ) {
						$state['value_found'] = $state['value_found'] . $value;
					}
					break;

				case 'esc_quote':
					if ( $state['is_text_block'] ) {
						$state['value_found'] = $state['value_found'] . '"';
					}
					break;
			}
		}

    if ( $state['key_found'] && $state['value_found'] ) {
      $data[ $state['key_found'] ] = $state['value_found'];
    }

		$data = array_filter( $data, function ( $item ) {
			if ( $item === 0 || $item === '0' ) {
				return true;
			}

			return ! empty( $item );
		} );

		$this->set_properties( $data );
	}

	private function set_properties( $data ) {
		$this->items = $data;
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
		  'opening_par'  => '\(',
		  'comma'        => ',',
		  'bool'         => 'true|false',
		  'colon'        => ':',
		  'comma'        => ',',
		  'string'       => '[a-zA-Z_\s\'\.$&+;=?@#|<>.\^*()%!-]+',
		  'num'          => '[0-9]+',
		  'quote'        => '[\"\\\']',
		  'esc_quote'    => '\\\\"',
		  'closing_para' => '\\)',
		);
	}
}
