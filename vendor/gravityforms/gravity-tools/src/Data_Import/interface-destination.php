<?php

namespace Gravity_Forms\Gravity_Tools\Data_Import;

/**
 * Destination Interface
 *
 * A Destination is any system/context to which the imported data can be sent.
 */
interface Destination {

	/**
	 * Get the valid keys for this Destination.
	 *
	 * @param array @args
	 *
	 * @return string[]
	 */
	public function keys( $args = array() );

	/**
	 * Determine if this Destination has the given key.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has_key( $key );

	/**
	 * Store the given array of records into this Destination.
	 *
	 * @param Record[] $records
	 *
	 * @return void
	 */
	public function store( $records );

	/**
	 * Check for duplicate records already existing within this Destination.
	 *
	 * @param Record $record
	 * @param string $check_key
	 *
	 * @return bool
	 */
	public function check_for_duplicates( $record, $check_key );
}
