<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Utils;

/**
 * Relationship
 *
 * Defines relationships between two object types.
 *
 * $from - The object to connect from
 * $to = The object to connect to
 * $cap - The minimum capability required for accessing this relationship.
 * $is_reverse - used to indicate that the relationship should use a reversed table name
 *
 * Example:
 *
 * $group_contact = new Relationship( 'group', 'contact', 'manage_options', false )
 *
 * This would result in a relationship from groups to contacts, and ensure that only
 * users with the `manage_options` capability are able to access it. When the relationship
 * is queried, the system will look in a table with `_group_contact` as the suffix.
 *
 *
 * Example 2:
 *
 * $contact_group = new Relationship( 'contact', 'group', 'manage_options', true )
 *
 * This would result in a relationship from contacts to groups, again restricted to users
 * with the `manage_options` capability. Since $is_reverse is `true`, queries would continue
 * to look for a table with `_group_contact` as the suffix, when normally it would attempt to
 * locate a table with the suffix of `_contact_group`. This setup allows you to use a single
 * lookup table for both directions of the relationship.
 */
class Relationship {

	/**
	 * @var string The object type to connect from.
	 */
	protected $from;

	/**
	 * @var string The object type to connect to.
	 */
	protected $to;

	/**
	 * @var string The minimum WordPress role or capability required for accessing this relationship
	 *             from within Queries and Mutations.
	 */
	protected $cap;

	/**
	 * @var boolean Indicates if this relationship is the reversal of another relationship and should
	 *              use the original's lookup  table for queries.
	 */
	protected $is_reverse;

	/**
	 * Constructor
	 *
	 * @param $from
	 * @param $to
	 * @param $cap
	 * @param $is_reverse
	 */
	public function __construct( $from, $to, $cap, $is_reverse = false ) {
		$this->from       = $from;
		$this->to         = $to;
		$this->cap        = $cap;
		$this->is_reverse = $is_reverse;
	}

	/**
	 * Public $from accessor.
	 *
	 * @return string
	 */
	public function from() {
		return $this->from;
	}

	/**
	 * Public $to accessor.
	 *
	 * @return string
	 */
	public function to() {
		return $this->to;
	}


	/**
	 * Public $cap accessor.
	 *
	 * @return string
	 */
	public function cap() {
		return $this->cap;
	}

  /**
   * Public $is_reverse accessor
   *
   * @return string
   */
  public function is_reverse() {
    return $this->is_reverse;
  }

	/**
	 * Whether the current user can access this relationship. (Uses current_user_can() by default).
	 *
	 * @return bool
	 */
	public function has_access() {
		return current_user_can( $this->cap );
	}

	/**
	 * Determines the correct table suffix when querying lookup tables.
	 *
	 * When $is_reverse is `true`, the suffix has the object type slugs swapped.
	 *
	 * @return string
	 */
	public function get_table_suffix() {
		if ( $this->is_reverse ) {
			return sprintf( '%s_%s', $this->to, $this->from );
		}

		return sprintf( '%s_%s', $this->from, $this->to );
	}

}
