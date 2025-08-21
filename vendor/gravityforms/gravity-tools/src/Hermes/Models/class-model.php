<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Models;

use Gravity_Forms\Gravity_Tools\Hermes\Utils\Relationship_Collection;

/**
 * Model
 *
 * This provides the base abstract contract for Models in Hermes. A Model is responsible
 * for defining all of the fields, relationships, and permissions surrounding a given
 * object/data type.
 */
abstract class Model {

	/**
	 * The type of object this model represents.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * The minimum capability required for a given user to be able to access
	 * this object's data.
	 *
	 * @var string
	 */
	protected $access_cap = '';

	/**
	 * If a model needs to support ad-hoc defined meta fields (i.e., fields that
	 * are defined by the user and are not defined explicitly in the model), set
	 * this to true.
	 *
	 * @var bool
	 */
	protected $supports_ad_hoc_fields = false;

	/**
	 * If your model needs to use an explicit/non-standard table, set it here.
	 * Do not include the DB prefix in the table name.
	 *
	 * @var bool|string
	 */
	protected $forced_table_name = false;


	/**
	 * If your model supports Full Text search fields, list them here.
	 *
	 * @var array
	 */
	protected $searchable_fields = array();

	/**
	 * Concrete Models must implement the public ::relationships() method
	 * in order to define any relationships this object type may have.
	 *
	 * @return Relationship_Collection
	 */
	abstract public function relationships();

	/**
	 * Determine if this model supports ad hoc field
	 * definitions.
	 *
	 * @return bool
	 */
	public function supports_ad_hoc_fields() {
		return $this->supports_ad_hoc_fields;
	}

	/**
	 * Accessor for forced_table_name.
	 *
	 * @return bool|string
	 */
	public function forced_table_name() {
		return $this->forced_table_name;
	}

	/**
	 * Accessor for the type property
	 *
	 * @return string
	 */
	public function type() {
		return $this->type;
	}

	/**
	 * Accessor for $searchable_fields
	 *
	 * @return array
	 */
	public function searchable_fields() {
		return $this->searchable_fields;
	}

	/**
	 * Concrete Models must define the specific Fields and Field Types this
	 * object type has. Any request regarding this object type will use this
	 * array to determine if a given field should be evaluated, and how to
	 * validate it.
	 *
	 * In the Database design, tables for this object type should have columns
	 * that strictly match the field names outlined here. For instance, if a field
	 * called "first_name" is defined here, a column of "first_name" must exist in
	 * the DB table for this object.
	 *
	 * @return array
	 */
	public function fields() {
		return array();
	}

	/**
	 * Concrete Models must define the specific Fields and Field Types this object
	 * type supports for *meta* fields. Meta fields differ from typical fields in that
	 * they do not require specific columns in the base Database table but are rather stored
	 * in a meta table. This allows for flexibility in adding new fields to a given object type
	 * after code has been deployed to production while avoiding having to modify DB tables.
	 *
	 * @return array
	 */
	public function meta_fields() {
		return array();
	}

	/**
	 * Checks the current user's access for this object type.Typically this should
	 * not be overwritten in the concrete Model, except for cases in which custom
	 * access functionality is required.
	 *
	 * @return bool
	 */
	public function has_access() {
		return current_user_can( $this->access_cap );
	}

	/**
	 * Concrete Models may define various transformations that can be applied to queried data. This
	 * is useful in situations where the data being stored for a given model's field needs to be returned
	 * in various formats. For instance, the data stored could be a post ID, and the transformation could return
	 * various aspects of that post, such as the Post Title, Description, etc. Other use-cases are things such as
	 * converting to all-caps, translating strings to other languages, or converting currencies.
	 *
	 * @return array
	 */
	public function transformations() {
		return array();
	}

	/**
	 * Using the given transformation type, pass a value through a transformation and return the result. The
	 * given transformation must be present in the transformations() array of this model.
	 *
	 * @param string $transformation_name
	 * @param mixed  $transformation_arg
	 * @param mixed  $value
	 *
	 * @return mixed
	 */
	public function handle_transformation( $transformation_name, $transformation_arg, $value ) {
		if ( ! isset( $this->transformations()[ $transformation_name ] ) ) {
			throw new \InvalidArgumentException( 'Attempting to call invalid transformation type ' . $transformation_name . ' on object type ' . $this->type );
		}

		$transformation = $this->transformations()[ $transformation_name ];

		return call_user_func( $transformation, $transformation_arg, $value );
	}
}
