<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens;

class Arguments_Token extends Token {

	protected $type = 'Arguments';

	protected $items = array();

	private $comparator_strings = array(
		'gte' => '>=',
		'lte' => '<=',
		'ne' => '!=',
		'gt' => '>',
		'lt' => '<',
		'in' => 'in',
	);

	public function items() {
		return $this->items;
	}

	public function parse( $contents, $args = array() ) {
		preg_match_all( $this->get_parsing_regex(), $contents, $results );

		if ( count( $results ) < 4 ) {
			// Something has gone terrible awry, bail.
			return;
		}

		$arguments = array();

		$keys   = $results[1];
		$values = $results[2];

		foreach ( $keys as $idx => $key ) {
			$condition_data = $this->get_condition_data_from_key( $key );
			$value          = $values[ $idx ];
			$arguments[]    = array(
				'key'        => trim( $condition_data['key'], '" ' ),
				'comparator' => $condition_data['comparator'],
				'value'      => trim( $value, '" ' ),
			);
		}

		$this->items = $arguments;
	}

	public function regex_types() {
		return array(
			'argument_pair' => '([a-zA-z0-9_-]*):([^,\)]+)',
		);
	}

	public function children() {
		return $this->items();
	}

	private function get_condition_data_from_key( $key_to_check ) {
		$comparator = '=';
		$key        = $key_to_check;

		foreach ( $this->comparator_strings as $check => $result ) {
			if ( strpos( $key_to_check, $check ) !== false ) {
				$key        = str_replace( '_' . $check, '', $key_to_check );
				$comparator = $result;

				return array(
					'comparator' => $comparator,
					'key'        => $key,
				);
			}
		}

		return array(
			'comparator' => $comparator,
			'key'        => $key,
		);
	}

}
