<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Utils;

/**
 * Relationshp Collection
 *
 * A simple collection handling Relationship objects.
 */
class Relationship_Collection {

	/**
	 * @var Relationship[]
	 */
	protected $relationships;

	/**
	 * @param Relationship[] $relationships
	 */
	public function __construct( $relationships = array() ) {
		$this->relationships = $relationships;
	}

	/**
	 * Add a Relationship to the collection.
	 *
	 * @param Relationship $relationship
	 *
	 * @return void
	 */
	public function add( Relationship $relationship ) {
		$this->relationships[] = $relationship;
	}

	/**
	 * Get a given Relationship by the related object.
	 *
	 * @param string $related_object
	 *
	 * @return Relationship|mixed|null
	 */
	public function get( $related_object ) {
		if ( ! $this->has( $related_object ) ) {
			return null;
		}

		$relationship = array_filter( $this->relationships, function ( $relationship ) use ( $related_object ) {
			return $relationship->to() === $related_object;
		} );

		return array_shift( $relationship );
	}

	/**
	 * Check if the collection contains a relationship for the given object type.
	 *
	 * @param string $related_object
	 *
	 * @return bool
	 */
	public function has( $related_object ) {
		$relationship = array_filter( $this->relationships, function ( $relationship ) use ( $related_object ) {
			return $relationship->to() === $related_object;
		} );

		return ! empty( $relationship );
	}
	
	/**
	 * Get all the relationships currently registered to collection.
	 *
	 * @return array
	 */
	public function all() {
		return $this->relationships;
	}

}
