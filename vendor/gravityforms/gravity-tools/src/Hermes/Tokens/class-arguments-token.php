<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens;

class Arguments_Token extends Token {

	protected $type = 'Arguments';

	protected $items = array();

	private $comparator_strings = array(
		'_gte' => '>=',
		'_lte' => '<=',
		'_ne'  => '!=',
		'_gt'  => '>',
		'_lt'  => '<',
		'_in'  => 'in',
	);

	public function items() {
		return $this->items;
	}

	public function parse( $contents, $args = array() ) {
		preg_match_all( $this->get_parsing_regex(), $contents, $parts );

		$matches = $parts[0];
		$marks   = $parts['MARK'];

		// Tracking values.
		$in_text       = false;
		$current_value = '';
		$current_key   = '';
		$is_key        = true;
		$is_value      = false;

		while ( ! empty( $matches ) ) {
			$value     = array_shift( $matches );
			$mark_type = array_shift( $marks );

			switch ( $mark_type ) {
				case 'quote':
					$in_text = ! $in_text;

					if ( ! $in_text ) {
						$is_key   = true;
						$is_value = false;
					}
					break;

				case 'colon':
					if ( $in_text ) {
						$current_value .= $value;
						break;
					}
					$is_key   = false;
					$is_value = true;
					break;

				case 'comma':
					if ( $in_text ) {
						$current_value .= $value;
						break;
					}
					$condition_data = $this->get_condition_data_from_key( $current_key );
					$data[]         = array(
						'key'        => trim( $condition_data['key'], '" \'' ),
						'comparator' => $condition_data['comparator'],
						'value'      => $this->parse_value( $current_value ),
					);

					$current_key   = '';
					$current_value = '';
					$is_key        = true;
					$is_value      = false;
					break;

				case 'value':
					if ( $is_key ) {
						$current_key .= $value;
						break;
					}

					if ( $is_value ) {
						$current_value .= $value;
						break;
					}

					break;
			}
		}

		if ( ! empty( $current_value ) && ! empty( $current_value ) ) {
			$condition_data = $this->get_condition_data_from_key( $current_key );
			$data[]         = array(
				'key'        => trim( $condition_data['key'], '" \'' ),
				'comparator' => $condition_data['comparator'],
				'value'      => $this->parse_value( $current_value ),
			);
		}

		$this->items = $data;
	}

	private function parse_value( $value ) {
		$value = trim( $value, '" \'' );

		if ( strpos( $value, '|' ) === false ) {
			return $value;
		}

		return explode( '|', $value );
	}

	public function regex_types() {
		return array(
			'quote' => '[\'"]',
			'comma' => ',',
			'colon' => ':',
			'value' => '[\|\sa-zA-Z0-9_-]',
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
				$key        = str_replace( $check, '', $key_to_check );
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
