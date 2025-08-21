<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Connect;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Token;

/**
 * Connection Values Token
 *
 * Used to hold the IDs to use for a given Connect mutation.
 */
class Connection_Values_Token extends Token {

	protected $type = 'Connection_Values';

	/**
	* An array of sub-arrays, each consisting of a `to` and `from` index.
	*
	* @var array[]
	*/
	protected $pairs = array();

	public function pairs() {
		return $this->pairs;
	}

	/**
	 * Return the $to and $from values as children.
	 *
	 * @return array
	 */
	public function children() {
		return $this->pairs;
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

		$matches = $results[0];
		$marks   = $results['MARK'];
		$state    = array();
    $data = array();

		while ( ! empty( $matches ) ) {
			$value     = array_shift( $matches );
			$mark_type = array_shift( $marks );

      switch( $mark_type ) {
        case 'argument_pair':
          $parts = explode( ':', $value );
          if ( count( $parts ) < 2 ) {
            break;
          }

          $key = trim( $parts[0] );
          $this_value = trim( $parts[1] );

          if ( ! in_array( $key, array( 'to', 'from' ) ) ) {
            break;
          }

          $state[ $key ] = $this_value;
          break;
        case 'splitter':
          // We don't have a to and from, bail.
          if ( empty( $state['to'] ) || empty( $state['from'] ) ) {
            $state = array();
            break;
          }

          $data[] = $state;
          $state = array();
          break;
      }
    }

    if ( ! empty( $state['to'] ) && ! empty( $state['from'] ) ) {
      $data[] = $state;
      $state = array();
    }

		$this->pairs = $data;
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
			'argument_pair' => '([a-zA-z0-9_-]*):([^,\}\)]+)',
      'splitter' => '\},',
		);
	}
}

