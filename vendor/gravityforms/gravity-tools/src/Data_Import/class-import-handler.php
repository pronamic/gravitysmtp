<?php

namespace Gravity_Forms\Gravity_Tools\Data_Import;

class Import_Handler {

	/**
	 * @var Source
	 */
	protected $source;

	/**
	 * @var Destination
	 */
	protected $destination;

	public function __construct( Source $source, Destination $destination ) {
		$this->source      = $source;
		$this->destination = $destination;
	}

	public function handle( $data, $map, $additional_args = array(), $count = -1, $offset = -1 ) {
		$this->source->set_data( $data );

		$records = $count === -1 ? $this->source->records( $additional_args ) : $this->source->slice( $count, $offset, $additional_args );
		$to_be_inserted = array();

		foreach ( $records as $record ) {
			$to_be_inserted[] = $this->map_single_record( $record, $map );
		}

		$this->destination->store( $to_be_inserted );
	}

	protected function map_single_record( Record $record, $map ) {
		$insertion_data = array();

		foreach ( $map as $source_key => $destination_key ) {
			if ( ! $this->destination->has_key( $destination_key ) ) {
				throw new \InvalidArgumentException( sprintf( 'Attempting to map key %s to invalid destination field %s.', $source_key, $destination_key ) );
			}

			if ( ! $this->source->has_key( $source_key ) ) {
				throw new \InvalidArgumentException( sprintf( 'Attempting to map invalid source key %s.', $source_key ) );
			}
		
			$insertion_data[ $destination_key ] = $record->value_for_key( $source_key );
		}

		return $insertion_data;
	}
}
