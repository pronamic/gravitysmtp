<?php

namespace Gravity_Forms\Gravity_Tools\System_Report;

use Environment_Details_Report_Details;

class System_Report_Repository {

	/**
	 * @var System_Report_Group[]
	 */
	protected $groups = array();

	private static $instance;

	public function __construct( $init_empty = false ) {
		if ( $init_empty ) {
			return;
		}
		$this->setup_environment_details();
	}

	private function setup_environment_details() {
		$environment_details = new Environment_Details_Report_Details();
		$groups = $environment_details->get_environment_details();

		foreach( $groups as $key => $group ) {
			$this->add( $key, $group );
		}
	}

	public static function instance( $init_empty = false ) {
		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		return new self( $init_empty );
	}

	public function all() {
		return $this->groups;
	}

	public function get( $name ) {
		if ( ! isset( $this->groups[ $name ] ) ) {
			return null;
		}

		return $this->groups[ $name ];
	}

	public function delete( $name ) {
		unset( $this->groups[ $name ] );
	}

	public function has( $name ) {
		if ( ! isset( $this->groups[ $name ] ) ) {
			return false;
		}

		return true;
	}

	public function add( $name, System_Report_Group $group ) {
		$this->groups[ $name ] = $group;
	}

	public function as_array() {
		$response = array();

		foreach( $this->groups as $name => $group ) {
			$response[ $name ] = $group->as_array();
		}

		return $response;
	}

	public function as_string() {
		$response = '';

		foreach( $this->groups as $name => $group ) {
			$response .= sprintf( "%s\n", $name );
			$response .= $group->as_string();
			$response .= "\n";
		}

		return $response;
	}
}
