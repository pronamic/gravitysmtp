<?php

namespace Gravity_Forms\Gravity_SMTP\Models;

use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Models\Hydrators\Hydrator_Factory;
use Gravity_Forms\Gravity_SMTP\Models\Traits\Can_Compare_Dynamically;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Collection;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Parser;

class Event_Model {

	use Can_Compare_Dynamically;

	protected $table_name = 'gravitysmtp_events';

	/**
	 * @var Hydrator_Factory $hydrator_factory
	 */
	protected $hydrator_factory;

	/**
	 * @var Plugin_Opts_Data_Store
	 */
	protected $opts;

	/**
	 * @var Recipient_Parser
	 */
	protected $recipient_parser;

	protected $queryable = array(
		'id',
		'date_created',
		'date_updated',
		'service',
		'subject',
		'message',
		'status',
	);

	protected $fillable = array(
		'date_created',
		'date_updated',
		'service',
		'subject',
		'message',
		'extra',
		'status',
	);

	public function __construct( $hydrator_factory, $plugin_opts, $recipient_parser ) {
		$this->hydrator_factory = $hydrator_factory;
		$this->opts             = $plugin_opts;
		$this->recipient_parser = $recipient_parser;
	}

	protected function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->table_name;
	}

	public function all() {
		global $wpdb;

		$sql = 'SELECT * FROM %1$s ORDER BY %2$s DESC;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name(), 'date_updated' ), ARRAY_A );

		return $this->hydrate( $results );
	}

	public function find( $where ) {
		global $wpdb;

		$post_hydrate_filters = array();
		$table_name           = $this->get_table_name();
		$values               = array();
		$sql                  = "SELECT * FROM $table_name WHERE ";

		foreach ( $where as $condition ) {

			if ( count( $condition ) === 3 ) {
				$key        = $condition[0];
				$comparator = $condition[1];
				$value      = $condition[2];
			} else {
				$key        = $condition[0];
				$comparator = '=';
				$value      = $condition[1];
			}

			if ( ! in_array( $key, $this->queryable ) ) {
				$post_hydrate_filters[] = array( 'key' => $key, 'value' => $value, 'comparator' => $comparator );
				continue;
			}

			$sql      .= sprintf( '%s %s %%s AND ', $key, $comparator );
			$values[] = $value;
		}

		$sql = rtrim( $sql, ' AND ' ) . ';';
		if ( strpos( $sql, '%s' ) !== false ) {
			$sql = $wpdb->prepare( $sql, $values );
		} else {
			$sql = rtrim( $sql, ' WHERE;' ) . ';';
		}
		$results  = $wpdb->get_results( $sql, ARRAY_A );
		$hydrated = $this->hydrate( $results );

		return array_filter( $hydrated, function ( $row ) use ( $post_hydrate_filters ) {
			foreach ( $post_hydrate_filters as $ph_condition ) {
				if ( ! isset( $row[ $ph_condition['key'] ] ) ) {
					return false;
				}

				$value_a = $row[ $ph_condition['key'] ];
				$value_b = $ph_condition['value'];

				if ( ! $this->compare( $value_a, $value_b, $ph_condition['comparator'] ) ) {
					return false;
				}
			}

			return true;
		} );
	}

	public function slice( $count, $offset = 0 ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		$sql     = "SELECT * FROM $table_name LIMIT %d, %d;";
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $offset, $count ), ARRAY_A );

		return $this->hydrate( $results );
	}

	public function count( $search_term = null, $search_type = null ) {
		global $wpdb;
		$table_name = $this->get_table_name();
		$search_clause = null;

		if ( ! empty( $search_term ) ) {
			$search_clause = $this->get_search_clause( $search_term, $search_type );
			$search_clause = 'WHERE ' . preg_replace( '/ AND$/', '', $search_clause );
		}

		$sql = "SELECT COUNT(1) as 'count' FROM $table_name $search_clause;";
		$results = $wpdb->get_row( $sql, ARRAY_A );

		return $results['count'];
	}

	public function paginate( $page, $per_page, $max_date = false, $search_term = null, $search_type = null ) {
		global $wpdb;
		$table_name = $this->get_table_name();
		$offset = ( $page - 1 ) * $per_page;

		if ( ! $max_date ) {
			$max_date = current_time( 'mysql', true );
		}

		$search_clause = null;

		if ( ! empty( $search_term ) ) {
			$search_clause = $this->get_search_clause( $search_term, $search_type );
		}

		$prepared_sql = $wpdb->prepare(
			"SELECT * FROM $table_name WHERE $search_clause `date_created` <= %s ORDER BY `date_created` DESC LIMIT %d, %d",
			$max_date,
			$offset,
			$per_page
		);

		$results = $wpdb->get_results( $prepared_sql, ARRAY_A );

		return $this->hydrate( $results );
	}

	private function get_search_clause( $search_term, $search_type ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		switch( $search_type ) {
			case 'email_and_headers':
				$prepared_sql = $wpdb->prepare(
					"`extra` LIKE '%%%s%%' AND",
					$search_term
				);
				break;
			case 'content':
				$prepared_sql = $wpdb->prepare(
					"`message` LIKE '%%%s%%' AND",
					$search_term
				);
				break;
			case 'subject':
				$prepared_sql = $wpdb->prepare(
					"`subject` LIKE '%%%s%%' AND",
					$search_term
				);
				break;
			default:
				$prepared_sql = $wpdb->prepare(
					"( `subject` LIKE '%%%s%%' OR `message` LIKE '%%%s%%' OR `extra` LIKE '%%%s%%' ) AND",
					$search_term,
					$search_term,
					$search_term
				);
		}

		return $prepared_sql;
	}

	public function create( $service, $status, $to, $from, $subject, $message, $extra ) {
		if ( ! $this->is_logging_enabled() ) {
			return 0;
		}

		global $wpdb;

		$extra['to']   = $to;
		$extra['from'] = $from;

		$wpdb->insert(
			$this->get_table_name(),
			array(
				'date_created' => current_time( 'mysql', true ),
				'date_updated' => current_time( 'mysql', true ),
				'status'       => $status,
				'service'      => $service,
				'subject'      => $subject,
				'message'      => $message,
				'extra'        => serialize( $extra ),
			)
		);

		return $wpdb->insert_id;
	}

	public function update( $values, $id ) {
		global $wpdb;

		$self   = $this;
		$values = array_filter( $values, function ( $key ) use ( $self ) {
			return in_array( $key, $self->fillable );
		}, ARRAY_FILTER_USE_KEY );

		$wpdb->update(
			$this->get_table_name(),
			$values,
			array( 'id' => $id )
		);
	}

	public function delete( $id ) {
		global $wpdb;

		$wpdb->delete( $this->get_table_name(), array( 'id' => $id ) );
	}

	public function delete_before( $date ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$query      = $wpdb->prepare( "DELETE FROM $table_name WHERE `date_created` <= %s", $date );

		$wpdb->query( $query );
	}

	public function delete_all() {
		global $wpdb;
		$table_name = $this->get_table_name();

		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	public function get_counts() {
		global $wpdb;
		$table_name = $this->get_table_name();

		$sql            = "SELECT service, COUNT(id) AS count FROM {$table_name} GROUP BY service";
		$service_counts = $wpdb->get_results( $sql, ARRAY_A );

		$sql           = "SELECT status, COUNT(id) AS count FROM {$table_name} GROUP BY status";
		$status_counts = $wpdb->get_results( $sql, ARRAY_A );

		$total = array_sum( wp_list_pluck( $service_counts, 'count' ) );

		return array(
			'total'   => $total,
			'service' => $service_counts,
			'status'  => $status_counts,
		);
	}

	protected function hydrate( $rows ) {
		static $hydrators = array();

		foreach ( $rows as $idx => $row ) {
			$service = $row['service'];
			$extra   = strpos( $row['extra'], '{' ) === 0 ? json_decode( $row['extra'], true ) : unserialize( $row['extra'] );

			try {
				if ( isset( $hydrators[ $service ] ) ) {
					$hydrator = $hydrators[ $service ];
				} else {
					$hydrator              = $this->hydrator_factory->create( $service );
					$hydrators[ $service ] = $hydrator;
				}
			} catch ( \Exception $e ) {
				$hydrator = false;
			}

			$row['source']       = isset( $extra['source'] ) ? $extra['source'] : __( 'N/A', 'gravitysmtp' );
			$row['email_counts'] = $this->get_email_counts( $extra );
			if ( $hydrator ) {
				$rows[ $idx ] = $hydrator->hydrate( $row );
			} else {
				$rows[ $idx ] = $row;
			}
		}

		return $rows;
	}

	private function get_email_counts( $extra ) {
		return $this->recipient_parser->get_email_counts( $extra );
	}

	private function is_logging_enabled() {
		$logging_enabled = $this->opts->get( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_ENABLED, 'config', 'true' );

		if ( empty( $logging_enabled ) ) {
			$logging_enabled = true;
		} else {
			$logging_enabled = $logging_enabled !== 'false';
		}

		return $logging_enabled;
	}

}
