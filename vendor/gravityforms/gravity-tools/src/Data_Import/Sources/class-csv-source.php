<?php

namespace Gravity_Forms\Gravity_Tools\Data_Import\Sources;

use Gravity_Forms\Gravity_Tools\Data_Import\Record;
use Gravity_Forms\Gravity_Tools\Data_Import\Source;

class CSV_Source implements Source {

	private $raw_data;

	private $data;

	/**
	 * Parse and store the passed CSV data.
	 *
	 * @param string $data - The raw CSV string data
	 *
	 * @return void
	 */
	public function set_data( $data ) {
		$this->raw_data = $data;
		$rows           = array_map( 'str_getcsv', $data );
		$header         = array_shift( $rows );
		$csv            = array();

		foreach ( $rows as $row ) {
			$csv[] = array_combine( $header, $row );
		}

		$this->data = $csv;
	}

	/**
	 * Get all of the available keys from the CSV headers.
	 *
	 * @param array $args - An array of additional args, info, or data needed
	 *
	 * @return array
	 */
	public function keys( $args = array() ) {
		$data = $this->data;

		if ( empty( $data ) ) {
			return array();
		}

		$entry = array_shift( $data );

		return array_keys( $entry );
	}

	/**
	 * Determine if this CSV has a given key.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has_key( $key ) {
		return in_array( $key, $this->keys() );
	}

	/**
	 * Get the rows for this CSV.
	 *
	 * @param array $args
	 *
	 * @return Record[]
	 */
	public function records( $args = array() ) {
		$records = array();

		foreach ( $this->data as $row ) {
			$records[] = new Record( $row );
		}

		return $records;
	}

	/**
	 * Get the row count for this CSV.
	 *
	 * @param array $args
	 *
	 * @return int
	 */
	public function count( $args = array() ) {
		return count( $this->data );
	}

	/**
	 * Get a specific slice of rows from this CSV.
	 *
	 * @param int   $count
	 * @param int   $offset
	 * @param array $additional_args
	 *
	 * @return Record[]
	 */
	public function slice( $count, $offset, $additional_args = array() ) {
		return array_slice( $this->data, $offset, $count, true );
	}
}
