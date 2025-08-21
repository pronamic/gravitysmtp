<?php

namespace Gravity_Forms\Gravity_Tools\Data_Import;

/**
 * Source Interface
 *
 * A Source is any collection of data from which values can be mapped. In the case of a CSV import,
 * this would represent the CSV, for instance. This interface provides the contract for all of the functionality
 * required of a valid Source.
 */
interface Source {

	/**
	 * Set the internal $data property to whatever is passed.
	 *
	 * @param mixed $data
	 *
	 * @return void
	 */
	public function set_data( $data );

	/**
	 * Get all of the valid/available keys for this source.
	 *
	 * In something like a CSV, this would be the column headers. For a WP Post,
	 * it would be the various field key names, etc.
	 *
	 * @param array $args - An array of additional args, info, or data needed.
	 *
	 * @return array
	 */
	public function keys( $args = array() );

	/**
	 * Determine if this source has a given key.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has_key( $key );

	/**
	 * Get the records for this source.
	 *
	 * @param array $args
	 *
	 * @return Record[]
	 */
	public function records( $args = array() );

	/**
	 * Get the record count for this source.
	 *
	 * @param array $args
	 *
	 * @return int
	 */
	public function count( $args = array() );

	/**
	 * Get a specific slice of records from this source.
	 *
	 * @param int   $count
	 * @param int   $offset
	 * @param array $additional_args
	 *
	 * @return Record[]
	 */
	public function slice( $count, $offset, $additional_args = array() );
}
