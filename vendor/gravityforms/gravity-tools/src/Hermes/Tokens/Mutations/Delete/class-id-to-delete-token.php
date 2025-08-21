<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Delete;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Token;

/**
 * ID To Delete Token
 *
 * Stores the ID to delete for a Delete mutation.
 */
class ID_To_Delete_Token extends Token {

	protected $type = 'IDs_To_Delete';

	/**
	 * The IDs to delete.
	 *
	 * @var string
	 */
	protected $ids;

	/**
	 * Public accessor for $id.
	 *
	 * @return string
	 */
	public function ids() {
		return $this->ids;
	}

	/**
	 * Return the ID as an array as children.
	 *
	 * @return array
	 */
	public function children() {
		return $this->ids;
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
		$data    = array();

		while ( ! empty( $matches ) ) {
			$value     = array_shift( $matches );
			$mark_type = array_shift( $marks );

			switch ( $mark_type ) {
				case 'ids':
					$data['ids'] = json_decode( $value );
					break;
			}
		}

		if ( empty( $data['ids'] ) ) {
			throw new \InvalidArgumentException( 'Delete payload malformed. Check values and try again.', 490 );
		}

		$this->ids = $data['ids'];
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
			'ids' => '\[[^\]]+\]',
		);
	}
}
