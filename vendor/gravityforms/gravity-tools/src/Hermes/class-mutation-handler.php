<?php

namespace Gravity_Forms\Gravity_Tools\Hermes;

use Gravity_Forms\Gravity_Tools\Hermes\Runners\Connect_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Delete_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Disconnect_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Insert_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Update_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Generic_Mutation_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Utils\Model_Collection;

/**
 * The initial entry point for handling Mutation requests. For Query handling, see Query_Handler.
 */
class Mutation_Handler {

	/**
	 * The namespace to use when querying DB tables. The namespace is used after the $wpdb->prefix
	 * value and before the actual table name.
	 *
	 * Example:
	 *
	 * Passing `gravitytools` would result in a meta table name of `wp_gravitytools_meta`..
	 *
	 * @var string
	 */
	protected $db_namespace;

	/**
	 * The collection of models supported for queries.
	 *
	 * @var Model_Collection
	 */
	protected $models;

	/**
	 * A valid Query Handler for retrieving the resulting objects after an insert/update.
	 *
	 * @var Query_Handler
	 */
	protected $query_handler;

	/**
	 * The Runner for handling Insert mutations.
	 *
	 * @var Insert_Runner
	 */
	protected $insert_runner;

	/**
	 * The Runner for handling Delete mutations.
	 *
	 * @var Delete_Runner
	 */
	protected $delete_runner;

	/**
	 * The Runner for handling Update mutations.
	 *
	 * @var Update_Runner
	 */
	protected $update_runner;

	/**
	 * The Runner for handling Connect mutations.
	 *
	 * @var Connect_Runner
	 */
	protected $connect_runner;

	/**
	 * The Runner for handling Disconnect mutations.
	 *
	 * @var Disconnect_Runner
	 */
	protected $disconnect_runner;

	/**
	 * All runners registered to handler.
	 *
	 * @var Runner[]
	 */
	protected $all_runners;

	/**
	 * Constructor
	 *
	 * @param string           $db_namespace
	 * @param Model_Collection $models
	 * @param Query_Handler    $query_handler
	 * @param Runner[]         $runners
	 */
	public function __construct( $db_namespace, $models, $query_handler, $runners ) {
		$this->db_namespace      = $db_namespace;
		$this->models            = $models;
		$this->query_handler     = $query_handler;
		$this->insert_runner     = $runners['insert'];
		$this->delete_runner     = $runners['delete'];
		$this->update_runner     = $runners['update'];
		$this->connect_runner    = $runners['connect'];
		$this->disconnect_runner = $runners['disconnect'];
		$this->all_runners       = $runners;
	}

	/**
	 * Parse the provided Mutation string and execute the appropriate SQL queries to perform the
	 * mutation.
	 *
	 * @param string $mutation_string
	 * @param bool   $return
	 *
	 * @return void
	 */
	public function handle_mutation( $mutation_string, $return = false ) {
		global $wpdb;

		// Pass the string to the Generic Mutation token to determine the specific mutation type.
		$generic_mutation = new Generic_Mutation_Token( $mutation_string );

		/**
		 * Mutation_Token $mutation
		 */
		$mutation = $generic_mutation->mutation();

		// Ensure the object type in question is registered in our Model Collection.
		if ( ! $this->models->has( $mutation->object_type() ) ) {
			$error_message = sprintf( 'Mutation attempted with invalid object type: %s', $mutation->object_type() );
			throw new \InvalidArgumentException( $error_message, 470 );
		}

		$object_model = $this->models->get( $mutation->object_type() );

		// Ensure the querying user has the appropriate permissions to access data for this object.
		if ( ! $object_model->has_access() ) {
			$error_message = sprintf( 'Access not allowed for object type %s', $mutation->object_type() );
			throw new \InvalidArgumentException( $error_message, 403 );
		}

		// Handle the actual mutation based on the identified mutation type by calling its appropriate Runner.
		switch ( $mutation->operation() ) {
			case 'insert':
				$this->insert_runner->run( $mutation, $object_model, $return );
				break;
			case 'update':
				$this->update_runner->run( $mutation, $object_model, $return );
				break;
			case 'delete':
				$this->delete_runner->run( $mutation, $object_model, $return );
				break;
			case 'connect':
				$this->connect_runner->run( $mutation, $object_model, $return );
				break;
			case 'disconnect':
				$this->disconnect_runner->run( $mutation, $object_model, $return );
				break;
			default:
				if ( array_key_exists( $mutation->operation(), $this->all_runners ) ) {
					$this->all_runners[ $mutation->operation() ]->run( $mutation, $object_model, $return );
					break;
				}
				break;
		}
	}
}
