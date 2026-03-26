<?php

namespace Gravity_Forms\Gravity_Tools\System_Report;

class System_Report_Item {

	protected $key;

	protected $value;

	protected $is_sensitive;

	protected $obfuscated_value = '**********';

	public function __construct( $key, $value, $is_sensitive = false ) {
		$this->key          = $key;
		$this->value        = $value;
		$this->is_sensitive = $is_sensitive;
	}

	public function key() {
		return $this->escape( $this->key );
	}

	public function value() {
		if ( $this->is_sensitive ) {
			return $this->obfuscated_value;
		}

		return $this->escape( $this->value );
	}

	public function is_sensitive() {
		return $this->is_sensitive;
	}

	public function as_array() {
		return array(
			'key'   => $this->key(),
			'value' => $this->value(),
		);
	}

	public function as_string() {
		return sprintf( "%s: %s\n", $this->key(), $this->value() );
	}

	private function escape( $string ) {
		return strip_tags( $string, array( 'a' ) );
	}
}
