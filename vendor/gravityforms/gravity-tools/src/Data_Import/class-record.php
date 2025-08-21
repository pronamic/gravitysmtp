<?php

namespace Gravity_Forms\Gravity_Tools\Data_Import;

/**
 * Record
 *
 * A Record represents a single piece of information from a Source. In a CSV import
 * it would represent a row, in a WP Post import it would represent a single Post object,
 * and so on.
 */
class Record {

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * Record constructor
	 *
	 * @param array $data - The data for this record.
	 *
	 * @return void
	 */
	public function __construct( $data ) {
		$this->data = $data;
	}

	/**
	 * Getter for the $data property.
	 *
	 * @return array
	 */
	public function data() {
		return $this->data;
	}

	/**
	 * Get a value from this record for the given key.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function value_for_key( $key ) {
		if ( ! $this->has_key( $key ) ) {
			throw new \InvalidArgumentException( sprintf( 'Key %s does not exist on current record.', $key ) );
		}

		return $this->data[ $key ];
	}

	/**
	 * Retrieve the keys for this record.
	 *
	 * @return string[]
	 */
	public function keys() {
		return array_keys( $this->data );
	}

	/**
	 * Determine if this record has the given key.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has_key( $key ) {
		return array_key_exists( $key, $this->data );
	}
}
