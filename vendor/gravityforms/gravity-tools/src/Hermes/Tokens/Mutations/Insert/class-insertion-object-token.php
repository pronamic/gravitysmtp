<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Insert;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Token;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Token_From_Array;

/**
 * Insertion Object Token
 *
 * A token holding the values for an object to be inserted into the Database.
 */
class Insertion_Object_Token extends Token_From_Array {

	protected $type = 'insertion_object';

	/**
	 * The fields to insert for this object.
	 *
	 * @var array
	 */
	protected $items;

	protected $object_type;

	protected $is_child;

	protected $parent_object_type;

	/**
	 * Return $items as children.
	 *
	 * @return array
	 */
	public function children() {
		return $this->items;
	}

	public function object_type() {
		return $this->object_type;
	}

	public function is_child() {
		return $this->is_child;
	}

	public function parent_object_type() {
		return $this->parent_object_type;
	}
	/**
	 * Parse the string contents to values.
	 *
	 * @return void
	 */
	public function parse( &$matches, &$marks, $additional_args = array() ) {
		$fields = $additional_args['fields'];
		$this->items = $fields;
		$this->object_type = $additional_args['object_type'];
		$this->is_child = $additional_args['is_child'];
		$this->parent_object_type = $additional_args['parent_object_type'];
	}

	/**
	 * The regex types to use while parsing.
	 *
	 * $key represents the MARK type, while the $value represents the REGEX string to use.
	 *
	 * @return string[]
	 */
	public function regex_types() {
		return array(
			'relatedObject' => '',
			'value' => ':[^,\}]+',
			'key'   => '[a-zA-Z0-9_\-]+',
		);
	}

}
