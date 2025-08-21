<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Utils;

use Gravity_Forms\Gravity_Tools\Hermes\Models\Model;

/**
 * Model Collection
 *
 * A simple collection class for storing registered Models in the system.
 */
class Model_Collection {

	/**
	 * @var Model[]
	 */
	protected $models = array();

	/**
	 * Register a model type.
	 *
	 * @param string $type
	 * @param Model  $model
	 *
	 * @return void
	 */
	public function add( $type, Model $model ) {
		$this->models[ $type ] = $model;
	}


	/**
	 * Unregister/remove a Model type.
	 *
	 * @param string $type
	 *
	 * @return void
	 */
	public function remove( $type ) {
		unset( $this->models[ $type ] );
	}

	/**
	 * Check if a Model of a given type exists in the collection.
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function has( $type ) {
		return array_key_exists( $type, $this->models );
	}

	/**
	 * Get a Model from the collection by its type.
	 *
	 * @param string $type
	 *
	 * @return Model|null
	 */
	public function get( $type ) {
		if ( ! $this->has( $type ) ) {
			return null;
		}

		return $this->models[ $type ];
	}

	/**
	 * Get all registered models from collection.
	 *
	 * @return Model[]
	 */
	public function all() {
		return $this->models;
	}
}

