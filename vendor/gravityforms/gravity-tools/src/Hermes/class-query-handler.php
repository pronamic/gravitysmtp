<?php

namespace Gravity_Forms\Gravity_Tools\Hermes;

use Gravity_Forms\Gravity_Tools\Hermes\Models\Model;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Schema_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Data_Object_From_Array_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Query_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Utils\Model_Collection;
use InvalidArgumentException;

/**
 * Query Handler
 *
 * The entry point for parsing queries made to Hermes. For Mutations, see Mutation_Handler.
 */
class Query_Handler {

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
   * @var Schema_Runner
   */
  protected $schema_runner;

  /**
   * Any fields that are global to all queries.
   *
   * @var array
   */
  private $global_fields = array(
    'aggregate',
  );

  /**
   * Constructor
   *
   * @param string $db_namespace
   */
  public function __construct( $db_namespace, Model_Collection $models, Schema_Runner $schema_runner ) {
    $this->db_namespace  = $db_namespace;
    $this->models        = $models;
    $this->schema_runner = $schema_runner;
  }

  /**
   * Parse the given query string text and perform the appropriate database queries to return
   * the requested data structure.
   *
   * @param string $query_string
   *
   * @return array
   */
  public function handle_query( $query_string, $return = false ) {
    global $wpdb;

    // Parse to token array
    $query_token = new Query_Token( $query_string );
    $data        = array();

    // Use the Token to generate recursive SQL.
    foreach ( $query_token->children() as $object ) {
      $object_name = ! empty( $object->alias() ) ? $object->alias() : $object->object_type();

      if ( $object->object_type() === '__schema' ) {
        $data[ $object_name ] = $this->get_schema_values_for_query( $object );
        continue;
      }

      $sql                  = $this->recursively_generate_sql( $object );
      $transformations      = $this->recursively_get_transformations( $object );
      $data[ $object_name ] = array(
        'sql'             => sprintf( 'SELECT %s', $sql ),
        'transformations' => $transformations,
      );
    }

    $results = array();

    // Decode the results and set them up for return.
    foreach ( $data as $data_group_name => $data_group_values ) {
      // Schema values do not need to be queried; just return the rows as-is.
      if ( isset( $data_group_values['schema'] ) ) {
        $results[ $data_group_name ] = $data_group_values['schema'];
        continue;
      }

      $query_results = $wpdb->get_results( $data_group_values['sql'], ARRAY_A );
      $rows          = array();

      foreach ( $query_results as $query_result ) {
        $json_data    = array_shift( $query_result );
        $decoded_data = json_decode( $json_data, true );
        $rows[]       = $decoded_data;
      }

      $rows = $this->recursively_apply_transformations( $rows, $data_group_values['transformations'] );

      $results[ $data_group_name ] = $rows;
    }

		if ( $return ) {
			return $results;
		}

    wp_send_json_success( $results );
  }

  /**
   * Loops through the objects in the Query String and recursively generates the appropriate
   * SQL for the related query. Supports an infinite level of nesting, but caution should be used when
   * performing deeply-nested queries as performance may be impacted.
   *
   * @return string
   */
  public function recursively_generate_sql( Data_Object_From_Array_Token $data, $idx_prefix = null, $parent_table = false, $parent_object_type = false ) {
    $object_type = $data->object_type();
    $object_name = ! empty( $data->alias() ) ? $data->alias() : $data->object_type();

    // Ensure the object type being queried exists as a Model.
    if ( ! $this->models->has( $object_type ) ) {
      $error_message = sprintf( 'Attempting to access invalid object type %s', $object_type );
      throw new InvalidArgumentException( $error_message, 460 );
    }

    $object_model = $this->models->get( $object_type );

    // Ensure that the querying user has the appropriate permissions to access object.
    if ( ! $object_model->has_access() ) {
      $error_message = sprintf( 'Access not allowed for object type %s', $object_type );
      throw new InvalidArgumentException( $error_message, 403 );
    }

    // Set up values for the table being queried.
    $table_name  = $this->compose_table_name( $object_type );
    $table_alias = $this->compose_table_alias( $object_name, $parent_table );

    // Categorized queried fields as either local or meta fields for future processing.
    $fields_to_process  = $data->fields();
    $categorized_fields = $this->categorize_fields( $object_model, $fields_to_process, $table_alias );

    $arguments = $data->arguments();

    // Set up data arrays for holding pieces of the SQL statement for later concatenation.
    $field_pairs   = array();
    $where_clauses = array();
    $join_clauses  = array();

    $field_sql     = null;
    $from_sql      = null;
    $join_sql      = null;
    $where_sql     = null;
    $group_sql     = null;
    $limit_sql     = null;
    $order_sql     = null;
    $separator_sql = null;

    // Arguments are present; parse them and add them to the appropriate SQL arrays.
    if ( ! empty( $arguments ) ) {
      $this->get_where_clauses_from_arguments( $where_clauses, $table_alias, $arguments, $object_model );
      $limit_sql = $this->get_limit_from_arguments( $arguments );
      $order_sql = $this->get_order_from_arguments( $arguments, $table_alias );
    }

    // Loop through each local field and generate the appropriate SQL chunks for retrieving the data.
    foreach ( $categorized_fields['local'] as $field_name => $field_alias ) {
      if ( is_a( $field_alias, Data_Object_From_Array_Token::class ) ) {
        $this_alias    = empty( $field_alias->alias() ) ? $field_alias->object_type() : $field_alias->alias();
        $sub_sql       = $this->recursively_generate_sql( $field_alias, null, $table_alias, $object_type );
        $sub_sql_parts = explode( '|gsmtpfieldsseparator|', $sub_sql );
        $sub_sql       = sprintf( '( SELECT JSON_ARRAYAGG( %s ) %s )', $sub_sql_parts[0], $sub_sql_parts[1] );
        $field_pairs[] = sprintf( '"%s", %s', $this_alias, $sub_sql );
        continue;
      }

      $field_pairs[] = sprintf( '"%s", %s.%s', $field_alias, $table_alias, $field_name );
    }

    $meta_table_name = $this->compose_table_name( 'meta' );

    // Loop through each meta field and compose the appropriate JOIN query for gathering its data.
    foreach ( $categorized_fields['meta'] as $field_name => $field_data ) {
      $value_clause   = $parent_table ? sprintf( '%s.meta_value', $field_data['lookup_table_alias'] ) : sprintf( 'MIN(%s.meta_value)', $field_data['lookup_table_alias'] );
      $field_pairs[]  = sprintf( '"%s", %s', $field_data['alias'], $value_clause, $field_name );
      $join_clauses[] = sprintf(
        'LEFT JOIN %s AS %s ON %s.object_type = "%s" AND %s.meta_name = "%s" AND %s.object_id = %s.id',
        $meta_table_name,
        $field_data['lookup_table_alias'],
        $field_data['lookup_table_alias'],
        $object_type,
        $field_data['lookup_table_alias'],
        $field_name,
        $field_data['lookup_table_alias'],
        $table_alias
      );
    }

    // If an aggregate is being called, add it as a subquery with the existing where conditions applied.
    if ( in_array( 'aggregate', $categorized_fields['global'] ) ) {
      $agg_alias = $categorized_fields['global']['aggregate'];
      $agg_sql   = sprintf( '"%s", (SELECT COUNT(*) FROM %s', $agg_alias, $table_name );

      if ( ! empty( $where_clauses ) ) {
        $agg_sql .= sprintf( ' WHERE %s', str_replace( $table_alias, $table_name, implode( ' AND ', $where_clauses ) ) );
      }

      $agg_sql .= ')';

      $field_pairs[] = $agg_sql;
    }

    // A parent table exists, meaning this is a nested query and requires a JOIN statement relating it
    // to the parent table.
    if ( $parent_table ) {
      $parent_model       = $this->models->get( $parent_object_type );
      $relationship       = $parent_model->relationships()->get( $object_type );
      $lookup_table_name  = $this->compose_join_table_name( $relationship->get_table_suffix() );
      $lookup_table_alias = sprintf( 'join_%s', $table_alias );
      $id_string          = sprintf( '%s_id', $object_type );
      $parent_id_string   = sprintf( '%s_id', $parent_object_type );
      $join_clauses[]     = sprintf( 'LEFT JOIN %s AS %s ON %s.id = %s.%s', $lookup_table_name, $lookup_table_alias, $table_alias, $lookup_table_alias, $id_string );
      $where_clauses[]    = sprintf( '%s.%s = %s.id', $lookup_table_alias, $parent_id_string, $parent_table );
    }

    // Concatenate each SQL array to generate the final SQL.
    $field_sql = implode( ', ', $field_pairs );

    $from_sql = sprintf( 'FROM %s AS %s', $table_name, $table_alias );

    $join_sql = implode( ' ', $join_clauses );

    if ( ! empty( $where_clauses ) ) {
      $where_sql = sprintf( 'WHERE %s', implode( ' AND ', $where_clauses ) );
    }

    $group_sql = null;

    if ( ! $parent_table ) {
      $group_sql = sprintf( 'GROUP BY %s.id', $table_alias );
    }

    if ( $parent_table ) {
      $separator_sql = '|gsmtpfieldsseparator|';
    }

    // Return the resulting SQL
    return sprintf( 'JSON_OBJECT( %s ) %s %s %s %s %s %s %s', $field_sql, $separator_sql, $from_sql, $join_sql, $where_sql, $group_sql, $order_sql, $limit_sql );
  }

  /**
   * Categorizes fields as either local (i.e., existing as columns within the table for the object) or meta
   * (i.e., existing as custom values in the meta table).
   *
   * @param Model  $object_model
   * @param array  $fields_to_process
   * @param string $table_alias
   *
   * @return array|array[]
   */
  protected function categorize_fields( $object_model, $fields_to_process, $table_alias ) {
    $categorized = array(
      'meta'   => array(),
      'local'  => array(),
      'global' => array(),
    );

    foreach ( $fields_to_process as $field ) {
      if ( is_a( $field, Data_Object_From_Array_Token::class ) ) {
        $child_type = $field->object_type();

        if ( ! $object_model->relationships()->has( $child_type ) ) {
          $error_string = sprintf( 'Attempting to access invalid related object %s for object type %s', $child_type, $object_model->type() );
          throw new InvalidArgumentException( $error_string, 455 );
        }

        $categorized['local'][ $field->alias() ] = $field;
        continue;
      }

      $field_name = $field->name();

      if ( ! in_array( $field_name, $this->global_fields ) && ! array_key_exists( $field_name, $object_model->fields() ) && ! array_key_exists( $field_name, $object_model->meta_fields() ) ) {
        $error_string = sprintf( 'Attempting to access invalid field %s on object type %s', $field_name, $object_model->type() );
        throw new InvalidArgumentException( $error_string, 450 );
      }

      $alias      = $field->alias();
      $identifier = $alias ? $alias : $field_name;

      if ( in_array( $field_name, $this->global_fields ) ) {
        $categorized['global'][ $field_name ] = $identifier;
      }

      if ( array_key_exists( $field_name, $object_model->fields() ) ) {
        $categorized['local'][ $field_name ] = $identifier;
      }

      if ( array_key_exists( $field_name, $object_model->meta_fields() ) ) {
        $categorized['meta'][ $field_name ] = array(
          'alias'              => $identifier,
          'lookup_table_alias' => sprintf( 'meta_%s_%s', $table_alias, $identifier ),
        );
      }
    }

    return $categorized;
  }

  /**
   * From the given array of $field_names, build the properly-structured SQL for selecting them.
   *
   * If a field is detected as a related object, we treat it as a top-level query and recursively
   * begin the SQL generation process for it.
   *
   * @param array  $field_names
   * @param string $table_alias
   * @param string $object_type
   *
   * @return array
   */
  protected function build_local_field_select_clauses( $field_names, $table_alias, $object_type ) {
    $pairs = array();

    foreach ( $field_names as $field_name => $alias ) {
      if ( is_a( $alias, Data_Object_From_Array_Token::class ) ) {
        $value   = '(' . $this->recursively_generate_sql( $alias, $field_name, $table_alias, $object_type ) . ')';
        $pairs[] = sprintf( '"%s", %s', $field_name, $value );
        continue;
      }

      $pairs[] = sprintf( '"%s", %s.%s', $alias, $table_alias, $field_name );
    }

    return $pairs;
  }

  /**
   * Generates the appropriate SQL clause(s) for selecting a Meta field. Meta fields exist in a lookup table, and thus require both a SELECT statement to properly
   * query the fields as well as a JOIN clause to join the meta table with a specific alias.
   *
   * @param string $meta_name          the name of the field being selected
   * @param string $alias              the alias to use when returning the selected field
   * @param string $object_type        the object type to grab the values from
   * @param string $parent_table_alias if present, the parent table this nested query belongs to
   * @param string $idx_prefix         If present, the previous IDX prefix used for the parent table.
   *                                   (to be prenended to this table's alias)
   *
   * @return array
   */
  protected function build_meta_query( $meta_name, $alias, $object_type, $parent_table_alias, $idx_prefix ) {
    global $wpdb;

    $meta_table_name  = $wpdb->prefix . $this->db_namespace . '_' . 'meta';
    $meta_table_alias = sprintf( 'meta_%s%s', $meta_name, is_null( $idx_prefix ) ? null : '_' . $idx_prefix );

    $select_clause = sprintf(
      '"%1$s", %2$s.meta_value',
      $alias,
      $meta_table_alias
    );

    $join_clause = sprintf(
      'LEFT JOIN %1$s AS %2$s ON %3$s.object_id = %4$s.id AND %5$s.object_type = "%6$s" AND %7$s.meta_name = "%8$s"',
      $meta_table_name,
      $meta_table_alias,
      $meta_table_alias,
      $parent_table_alias,
      $meta_table_alias,
      $object_type,
      $meta_table_alias,
      $meta_name
    );

    return array(
      'select_clause' => $select_clause,
      'join_clause'   => $join_clause,
    );
  }

  /**
   * Build a SQL WHERE clause from the given set of conditions.
   *
   * @param array $conditions
   *
   * @return void
   */
  protected function build_where_clauses( $conditions ) {
    $clauses = array();

    foreach ( $conditions as $condition ) {
      $column_name  = $condition['key'];
      $column_value = $condition['value'];
      $comparator   = $condition['comparator'];

      $clauses[] = sprintf( '%1$s %2$s %3$s', $column_name, $comparator, $column_value );
    }

    return $clauses;
  }

  /**
   * Compose the proper table name for a given object type.
   *
   * @param string $object_type
   *
   * @return string
   */
  private function compose_table_name( $object_type ) {
    global $wpdb;

    if ( ! $this->models->has( $object_type ) || ! $this->models->get( $object_type )->forced_table_name() ) {
      return sprintf( '%s%s_%s', $wpdb->prefix, $this->db_namespace, $object_type );
    }

    $object_model = $this->models->get( $object_type );

    return sprintf( '%s_%s', $wpdb->prefix, $object_model->forced_table_name() );
  }

  /**
   * Compose the appropriate join table name for a given suffix.
   *
   * @param string $suffix
   *
   * @return string
   */
  private function compose_join_table_name( $suffix ) {
    global $wpdb;

    return sprintf( '%s%s_%s', $wpdb->prefix, $this->db_namespace, $suffix );
  }

  /**
   * Composes a table alias to ensure every table has a unique name in the query.
   *
   * @param string $object_name
   * @param string $parent_table_name
   *
   * @return string
   */
  private function compose_table_alias( $object_name, $parent_table_name = false ) {
    if ( ! empty( $parent_table_name ) ) {
      return sprintf( '%s_%s', $parent_table_name, $object_name );
    }

    return sprintf( 'table_%s', $object_name );
  }

  /**
   * Search an array of arguments and return the appropriate SQL LIMIT clause from
   * the values.
   *
   * @param array $arguments
   *
   * @return string
   */
  private function get_limit_from_arguments( $arguments ) {
    $response = '';

    $limit = array_values(
      array_filter(
        $arguments,
        function ( $item ) {
          return $item['key'] === 'limit';
        }
    )
    );

    $offset = array_values(
      array_filter(
        $arguments,
        function ( $item ) {
          return $item['key'] === 'offset';
        }
    )
    );

    if ( ! empty( $limit ) ) {
      $response .= sprintf( 'LIMIT %s', $limit[0]['value'] );
    }

    if ( ! empty( $offset ) ) {
      $response .= sprintf( ' OFFSET %s', $offset[0]['value'] );
    }

    return $response;
  }

  private function get_order_from_arguments( $arguments, $table_alias ) {
    $response = '';

    $order = array_values(
      array_filter(
        $arguments,
        function ( $item ) {
          return $item['key'] === 'order';
        }
    )
    );

    $order_by = array_values(
      array_filter(
        $arguments,
        function ( $item ) {
          return $item['key'] === 'orderBy';
        }
    )
    );

    if ( empty( $order_by ) ) {
      return null;
    }

    if ( empty( $order ) ) {
      $order = array( array( 'value' => 'DESC' ) );
    }

    $response = sprintf( 'ORDER BY %s.%s %s', $table_alias, $order_by[0]['value'], $order[0]['value'] );

    return $response;
  }

  private function recursively_get_model_relationships( Model $object_model ) {
    $map = array();

    foreach ( $object_model->relationships()->all() as $relationship ) {
      if ( ! $relationship->has_access() || $relationship->is_reverse() ) {
        continue;
      }

      $new_object_model = $this->models->get( $relationship->to() );

      $children = $this->recursively_get_model_relationships( $new_object_model );

      $map[ $new_object_model->type() ] = $children;
    }

    return $map;
  }

  private function recursively_order_relationship_map( $parent_relationships, $relationship_map ) {
    $data = array();

    foreach ( $relationship_map as $key => $value ) {
      $model             = $this->models->get( $key );
      $searchable_fields = $model->searchable_fields();
      $children          = array();

      if ( ! empty( $value ) ) {
        $child_parent_relationships   = $parent_relationships;
        $child_parent_relationships[] = $key;
        $children                     = $this->recursively_order_relationship_map( $child_parent_relationships, $value );
      }

      $data[ $key ] = array(
        'parent_relationships' => $parent_relationships,
        'searchable_fields'    => $searchable_fields,
        'children'             => $children,
      );
    }

    return $data;
  }

  private function recursively_get_ids_from_relationship_map( &$ids, $search_term, $relationship_map ) {
    foreach ( $relationship_map as $object_type => $values ) {
      global $wpdb;
      $wpdb_prefix          = $wpdb->prefix;
      $db_namespace         = $this->db_namespace;
      $table_name           = sprintf( '%s%s_%s', $wpdb_prefix, $db_namespace, $object_type );
      $parent_relationships = array_reverse( $values['parent_relationships'] );

      $id_table_alias  = count( $values['parent_relationships'] ) > 0 ? sprintf( 'pt%s', count( $values['parent_relationships'] ) ) : 'mt';
      $id_column_alias = count( $values['parent_relationships'] ) > 0 ? sprintf( '%s.%s_id', $id_table_alias, $values['parent_relationships'][0] ) : 'id';
      $select_clause   = sprintf( 'SELECT %s AS id FROM %s AS mt', $id_column_alias, $table_name );

      $match_statements = array_map( function ( $field_name ) use ( $search_term ) {
        return sprintf( 'MATCH( mt.%s ) AGAINST( "%s*" IN BOOLEAN MODE )', $field_name, $search_term );
      }, $values['searchable_fields'] );

      $join_statements = array_map( function ( $related_type, $idx ) use ( $object_type, $db_namespace, $wpdb_prefix, $parent_relationships ) {
        $joined_type         = $idx === 0 ? $object_type : $parent_relationships[ $idx - 1 ];
        $joined_table_alias  = $idx === 0 ? 'mt' : sprintf( 'pt%s', $idx );
        $joined_table_column = $idx === 0 ? 'id' : sprintf( '%s_id', $parent_relationships[ $idx - 1 ] );
        $join_table_name     = sprintf( '%s%s_%s_%s', $wpdb_prefix, $db_namespace, $related_type, $joined_type );

        return sprintf( 'LEFT JOIN %s AS pt%s ON pt%s.%s_id = %s.%s', $join_table_name, $idx + 1, $idx + 1, $joined_type, $joined_table_alias, $joined_table_column );
      }, $parent_relationships, array_keys( $parent_relationships ) );

      $sql        = sprintf( '%s %s WHERE %s', $select_clause, implode( ' ', $join_statements ), implode( ' OR ', $match_statements ) );
      $results    = $wpdb->get_results( $sql, ARRAY_A );
      $result_ids = wp_list_pluck( $results, 'id' );

      $ids[] = $result_ids;

      if ( ! empty( $values['children'] ) ) {
        $this->recursively_get_ids_from_relationship_map( $ids, $search_term, $values['children'] );
      }
    }
  }

  /**
   * Get IDs for a search term from child objects.
   *
   * @param string $search_term
   *
   * @return array
   */
  private function get_aggregate_ids_for_search( $search_term, Model $parent_object_model ) {
    $relationship_map                                 = array();
    $relationship_map[ $parent_object_model->type() ] = $this->recursively_get_model_relationships( $parent_object_model );
    $relationship_map                                 = $this->recursively_order_relationship_map( array(), $relationship_map );
    $ids                                              = array();

    // $ids passed as reference, so it gets updated with values here.
    $this->recursively_get_ids_from_relationship_map( $ids, $search_term, $relationship_map );

    $all_ids = array();

    // flatten the multi-level $ids array into a single array of values
    foreach( $ids as $id_set ) {
      $all_ids = array_merge( $all_ids, $id_set );
    }

    // get rid of dupes
		$all_ids = array_unique( $all_ids );

		// remove empty values
		return array_filter( $all_ids );
  }

  /**
   * Search an array of arguments and compose the appropriate WHERE clause for the values provided.
   *
   * @param array  $where_clauses
   * @param string $table_alias
   * @param array  $arguments
   * @param Model  $object_model
   *
   * @return void
   */
  private function get_where_clauses_from_arguments( &$where_clauses, $table_alias, $arguments, $object_model ) {
    foreach ( $arguments as $argument ) {
      if ( $argument['key'] === 'order' || $argument['key'] === 'orderBy' || $argument['key'] === 'limit' || $argument['key'] === 'offset' ) {
        continue;
      }

      if ( $argument['key'] === 'search' ) {
        // If a search is present, we need to do some work to get an aggregate based on all the searchable fields.
        $ids             = $this->get_aggregate_ids_for_search( $argument['value'], $object_model );
        $where_clauses[] = sprintf( '%s.%s IN (%s)', $table_alias, 'id', implode( ', ', $ids ) );
        continue;
      }

      if ( $argument['comparator'] === 'in' ) {
        $in_vals = explode( '|', $argument['value'] );

        foreach ( $in_vals as $key => $value ) {
          $in_vals[ $key ] = sprintf( '"%s"', $value );
        }
        $in_string       = implode( ', ', $in_vals );
        $clause          = sprintf( '%s.%s IN (%s)', $table_alias, $argument['key'], $in_string );
        $where_clauses[] = $clause;
        continue;
      }

      $clause          = sprintf( '%s.%s %s "%s"', $table_alias, $argument['key'], $argument['comparator'], $argument['value'] );
      $where_clauses[] = $clause;
    }
  }

  /**
   * Traverse the Query Object and gather all the transformation arguments into a hierarchical array which can
   * be used to modify the actual data from the database.
   *
   * @param array $transformations
   *
   * @return array
   */
  private function recursively_get_transformations( Data_Object_From_Array_Token $data, $transformations = array() ) {
    foreach ( $data->fields() as $field ) {
      if ( is_a( $field, Data_Object_From_Array_Token::class ) ) {
        $this_alias                     = ! empty( $field->alias() ) ? $field->alias() : $field->object_type();
        $transformations[ $this_alias ] = $this->recursively_get_transformations( $field, $transformations );
      }

      $arguments = $field->arguments();

      if ( empty( $arguments ) ) {
        continue;
      }

      $transformations[ $field->alias() ] = array(
        'items' => array(),
      );

      foreach ( $arguments->items() as $argument ) {
        if ( strpos( $argument['key'], 'transform' ) !== false ) {
          $transformations[ $field->alias() ]['items'][ $argument['key'] ] = array(
            'key'         => $argument['key'],
            'value'       => $argument['value'],
            'object_type' => $data->object_type(),
          );
        }
      }
    }

    return $transformations;
  }

  /**
   * Traverse all of the data rows (and the corresponding array of transformations gathered previously) and apply
   * the transformations to the data.
   *
   * @param array $rows
   * @param array $transformations
   *
   * @return array
   */
  private function recursively_apply_transformations( $rows, $transformations ) {
    foreach ( $rows as $idx => $row ) {
      foreach ( $row as $key => $value ) {
        if ( ! array_key_exists( $key, $transformations ) ) {
          continue;
        }

        $local_transformations = $transformations[ $key ];

        if ( is_array( $value ) ) {
          $rows[ $idx ][ $key ] = $this->recursively_apply_transformations( $value, $local_transformations );
          continue;
        }

        if ( empty( $local_transformations['items'] ) ) {
          continue;
        }

        foreach ( $local_transformations['items'] as $transformation_name => $transformation_values ) {
          $object_model         = $this->models->get( $transformation_values['object_type'] );
          $rows[ $idx ][ $key ] = $object_model->handle_transformation( $transformation_name, $transformation_values['value'], $value );
        }
      }
    }

    return $rows;
  }

  public function get_schema_values_for_query( $object ) {
    $schema = $this->schema_runner->run( $object );

    return array(
      'schema'          => $schema,
      'transformations' => array(),
    );
  }
}
