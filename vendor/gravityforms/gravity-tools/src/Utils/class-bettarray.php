<?php

namespace Gravity_Forms\Gravity_Tools\Utils;

use ArrayAccess;

class Bettarray implements ArrayAccess {

	protected $array;

	public function __construct( array $array ) {
		$this->array = $array;
	}

	public function all() {
		return $this->array;
	}

	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->array[ $offset ] );
	}

	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->get_nested_value( $offset );
	}

	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->array[] = $value;
			return;
		}

		$this->update_nested_value( $offset, $value );
	}

	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		$this->delete_nested_value( $offset );
	}

	public function get( $key ) {
		return $this->get_nested_value( $key );
	}

	public function get_raw( $key ) {
		return $this->get_nested_value( $key, false );
	}

	public function set( $key, $value ) {
		return $this->update_nested_value( $key, $value );
	}

	public function delete( $key ) {
		return $this->delete_nested_value( $key );
	}

	public function slice( $offset, $count ) {
		$data = $this->array;

		$sliced = array_slice( $data, $offset, $count );

		return new self( $sliced );
	}

	public function pluck( $search_key ) {
		$results = array();

		foreach( $this->array as $row ) {
			if ( isset( $row[ $search_key ]) ) {
				$results[] = $row[ $search_key ];
			}
		}

		return new self( $results );
	}

	public function filter( callable $callback ) {
		$results = array();

		foreach( $this->array as $row ) {
			$matched = call_user_func( $callback, $row );
			if ( $matched ) {
				$results[] = $row;
			}
		}

		return new self( $results );
	}

	public function count() {
		return count( $this->array );
	}

	public function dd() {
		var_dump( $this->array );
		die();
	}

	public function dump() {
		var_dump( $this->array );
	}

	public function append( $key, $value ) {
		$existing = $this->get_nested_value( $key, false );

		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$existing[] = $value;

		$this->update_nested_value( $key, $existing );
	}

	public function amend( $key, $value ) {
		$existing = $this->get_nested_value( $key, false );

		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$existing = array_merge( $existing, $value );

		$this->update_nested_value( $key, $existing );
	}

	private function get_nested_value( $key, $as_bettarray = true ) {
		$current = $this->array;
		$key_arr = explode( '.', $key );

		while ( count( $key_arr ) ) {
			$new_key = array_shift( $key_arr );
			$current = isset( $current[ $new_key ] ) ? $current[ $new_key ] : array();
		}

		if ( is_array( $current ) && $as_bettarray ) {
			return new self( $current );
		}

		return $current;
	}

	private function update_nested_value( $key, $value ) {
		$current = &$this->array;
		$key_arr = explode( '.', $key );

		while ( count( $key_arr ) ) {
			$new_key = array_shift( $key_arr );

			if ( ! isset( $current[ $new_key ] ) ) {
				$current[ $new_key ] = array();
			}

			$current = &$current[ $new_key ];
		}

		$current = $value;
	}

	private function delete_nested_value( $key ) {
		$current = &$this->array;
		$key_arr = explode( '.', $key );

		while ( count( $key_arr ) > 1 ) {
			$new_key = array_shift( $key_arr );

			if ( ! isset( $current[ $new_key ] ) ) {
				$current[ $new_key ] = array();
			}

			$current = &$current[ $new_key ];
		}

		$new_key = array_shift( $key_arr );

		unset( $current[ $new_key ] );
	}
}
