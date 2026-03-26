<?php

namespace Gravity_Forms\Gravity_Tools\System_Report;

class System_Report_Group {

	/**
	 * @var System_Report_Item[]
	 */
	protected $items = array();

	public function all() {
		return $this->items;
	}

	public function add( $name, System_Report_Item $item ) {
		$this->items[ $name ] = $item;
	}

	public function delete( $name ) {
		unset( $this->items[ $name ] );
	}

	public function get( $name ) {
		if ( ! isset( $this->items[ $name ] ) ) {
			return null;
		}

		return $this->items[ $name ];
	}

	public function has( $name ) {
		if ( ! isset( $this->items[ $name ] ) ) {
			return false;
		}

		return true;
	}

	public function as_array() {
		return array_map( function( $item ) {
			return $item->as_array();
		}, $this->items );
	}

	public function as_string() {
		$response = '';

		foreach( $this->items as $item ) {
			$response .= $item->as_string();
		}

		return $response;
	}

}
