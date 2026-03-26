<?php

use Gravity_Forms\Gravity_Tools\System_Report\System_Report_Group;
use Gravity_Forms\Gravity_Tools\System_Report\System_Report_Item;
use Gravity_Forms\Gravity_Tools\Utils\Common;

class Environment_Details_Report_Details {

	protected $groups = array();

	private function add( $name, $group ) {
		$this->groups[ $name ] = $group;
	}

	public function get_environment_details() {
		if ( ! function_exists( 'get_locale' ) ) {
			return $this->groups;
		}

		$wordpress_environment_data = $this->get_wordpress_environment_data();
		$active_theme_data          = $this->get_active_theme_data();
		$active_plugins_data        = $this->get_active_plugins_data();
		$web_server_data            = $this->get_web_server_data();
		$php_data                   = $this->get_php_data();
		$database_server_data       = $this->get_database_server_data();
		$date_and_time_data         = $this->get_date_and_time_data();
		$translations               = $this->get_translations_data();

		$this->add( __( 'WordPress Environment', 'gravity' ), $wordpress_environment_data );
		$this->add( __( 'Active Theme', 'gravity' ), $active_theme_data );
		$this->add( __( 'Active Plugins', 'gravity' ), $active_plugins_data );
		$this->add( __( 'Web Server', 'gravity' ), $web_server_data );
		$this->add( __( 'PHP', 'gravity' ), $php_data );
		$this->add( __( 'Database Server', 'gravity' ), $database_server_data );
		$this->add( __( 'Date and Time', 'gravity' ), $date_and_time_data );
		$this->add( __( 'Translations', 'gravity' ), $translations );

		return $this->groups;
	}

	protected function get_translations_data() {
		$group = new System_Report_Group();

		$group->add( 'site_locale', new System_Report_Item( __( 'Site Locale', 'gravity' ), get_locale() ) );
		$group->add( 'user_locale', new System_Report_Item( sprintf( esc_html__( 'User (ID: %d) Locale', 'gravity' ), get_current_user_id() ), get_user_locale() ) );

		return $group;
	}

	protected function get_wordpress_environment_data() {
		$wp_cron_disabled  = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
		$alternate_wp_cron = defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON;

		$args = array(
			'timeout'   => 2,
			'body'      => 'test',
			'cookies'   => $_COOKIE,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);

		$filters_to_check = array(
			'pre_wp_mail' => has_filter( 'pre_wp_mail' ),
		);

		$registered_filters = array_keys( array_filter( $filters_to_check ) );

		$items = array(
			array(
				'label'        => esc_html__( 'Home URL', 'gravity' ),
				'value'        => get_home_url(),
			),
			array(
				'label'        => esc_html__( 'Site URL', 'gravity' ),
				'value'        => get_site_url(),
			),
			array(
				'label'        => esc_html__( 'REST API Base URL', 'gravity' ),
				'value'        => rest_url(),
			),
			array(
				'label'        => esc_html__( 'WordPress Version', 'gravity' ),
				'value'        => get_bloginfo( 'version' ),
			),
			array(
				'label'        => esc_html__( 'WordPress Multisite', 'gravity' ),
				'value'        => is_multisite() ? esc_html__( 'Yes', 'gravity' ) : esc_html__( 'No', 'gravity' ),
			),
			array(
				'label'        => esc_html__( 'WordPress Memory Limit', 'gravity' ),
				'value'        => WP_MEMORY_LIMIT,
			),
			array(
				'label'        => esc_html__( 'WordPress Debug Mode', 'gravity' ),
				'value'        => WP_DEBUG ? esc_html__( 'Yes', 'gravity' ) : esc_html__( 'No', 'gravity' ),
			),
			array(
				'label'        => esc_html__( 'WordPress Debug Log', 'gravity' ),
				'value'        => WP_DEBUG_LOG ? esc_html__( 'Yes', 'gravity' ) : esc_html__( 'No', 'gravity' ),
			),
			array(
				'label'        => esc_html__( 'WordPress Script Debug Mode', 'gravity' ),
				'value'        => SCRIPT_DEBUG ? __( 'Yes', 'gravity' ) : __( 'No', 'gravity' ),
			),
			array(
				'label'        => esc_html__( 'WordPress Cron', 'gravity' ),
				'value'        => ! $wp_cron_disabled ? __( 'Yes', 'gravity' ) : __( 'No', 'gravity' ),
			),
			array(
				'label'        => esc_html__( 'WordPress Alternate Cron', 'gravity' ),
				'value'        => $alternate_wp_cron ? __( 'Yes', 'gravity' ) : __( 'No', 'gravity' ),
			),
			array(
				'label'        => esc_html__( 'Registered Filters', 'gravity' ),
				'value'        => empty( $registered_filters ) ? esc_html__( 'N/A', 'gravity' ) : join( ', ', $registered_filters ),
			),
		);

		$group = new System_Report_Group();

		foreach ( $items as $item ) {
			$group->add( $item['label'], new System_Report_Item( $item['label'], $item['value'] ) );
		}

		return $group;
	}

	protected function get_active_theme_data() {
		$themes       = array();
		$active_theme = wp_get_theme();
		$parent_theme = $active_theme->parent();

		// Add active theme data
		$label = ! empty( $active_theme->get( 'ThemeURI' ) )
			? '<a class="gform-link" href="' . esc_url( $active_theme->get( 'ThemeURI' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $active_theme->get( 'Name' ) ) . '</a>'
			: esc_html( $active_theme->get( 'Name' ) );

		if ( ! empty( $active_theme->get( 'AuthorURI' ) ) ) {
			$author = '<a class="gform-link" href="' . esc_url( $active_theme->get( 'AuthorURI' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $active_theme->get( 'Author' ) ) . '</a>';
		} else {
			$author = preg_replace_callback( '/(<a[^>]*>)/', function ( $matches ) {
				preg_match( '/class="[^"]*"/', $matches[1], $class_matches );

				if ( empty( $class_matches ) ) {
					return str_replace( '<a', '<a class="gform-link"', $matches[1] );
				}

				return preg_replace( '/class="([^"]*)"/', 'class="$1 gform-link"', $matches[1] );
			}, $active_theme->get( 'Author' ) );
		}

		$value = wp_kses_post(
			sprintf(
				/* translators: 1: Theme author and URL. 2: Theme version. */
				__( 'by %1$s - %2$s', 'gravity' ),
				$author,
				$active_theme->get( 'Version' )
			)
		);

		$themes[] = array(
			'label'         => $label,
			'value'         => $value,
		);

		// Add parent theme data if it exists
		if ( $parent_theme instanceof \WP_Theme ) {
			$parent_label = ! empty( $parent_theme->get( 'ThemeURI' ) )
				? '<a class="gform-link" href="' . esc_url( $parent_theme->get( 'ThemeURI' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $parent_theme->get( 'Name' ) ) . ' (Parent)</a>'
				: esc_html( $parent_theme->get( 'Name' ) . ' (Parent)' );

			$parent_author_uri = $parent_theme->get( 'AuthorURI' );

			if ( ! empty( $parent_theme->get( 'AuthorURI' ) ) ) {
				$parent_author = '<a class="gform-link" href="' . esc_url( $parent_theme->get( 'AuthorURI' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $parent_theme->get( 'Author' ) ) . '</a>';
			} else {
				$parent_author = preg_replace_callback( '/(<a[^>]*>)/', function ( $matches ) {
					preg_match( '/class="[^"]*"/', $matches[1], $class_matches );

					if ( empty( $class_matches ) ) {
						return str_replace( '<a', '<a class="gform-link"', $matches[1] );
					}

					return preg_replace( '/class="([^"]*)"/', 'class="$1 gform-link"', $matches[1] );
				}, $parent_theme->get( 'Author' ) );
			}

			$parent_value = wp_kses_post(
				sprintf(
					/* translators: 1: Theme author and URL. 2: Theme version. */
					__( 'by %1$s - %2$s', 'gravity' ),
					$parent_author,
					$parent_theme->get( 'Version' )
				)
			);

			$themes[] = array(
				'label'         => $parent_label,
				'value'         => $parent_value,
			);
		}

		$group = new System_Report_Group();

		foreach ( $themes as $theme ) {
			$group->add( $theme['label'], new System_Report_Item( $theme['label'], $theme['value'] ) );
		}

		return $group;
	}

	protected function get_active_plugins_data() {
		$plugins = array();

		foreach ( get_plugins() as $plugin_path => $plugin ) {
			// If plugin is not active, skip it.
			if ( ! is_plugin_active( $plugin_path ) ) {
				continue;
			}

			$label  = isset( $plugin['PluginURI'] ) && ! empty( $plugin['PluginURI'] )
				? '<a class="gform-link" href="' . esc_url( $plugin['PluginURI'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $plugin['Name'] ) . '</a>'
				: esc_html( $plugin['Name'] );

			$author = $plugin['Author'];

			if ( isset( $plugin['AuthorURI'] ) && ! empty( $plugin['AuthorURI'] ) ) {
				$author = '<a class="gform-link" href="' . esc_url( $plugin['AuthorURI'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $plugin['Author'] ) . '</a>';
			} else {
				$author = preg_replace_callback( '/(<a[^>]*>)/', function ( $matches ) {
					preg_match( '/class="[^"]*"/', $matches[1], $class_matches );

					if ( empty( $class_matches ) ) {
						return str_replace( '<a', '<a class="gform-link"', $matches[1] );
					}

					return preg_replace( '/class="([^"]*)"/', 'class="$1 gform-link"', $matches[1] );
				}, $plugin['Author'] );
			}

			$value = wp_kses_post(
				sprintf(
					/* translators: 1: Plugin author and URL. 2: Plugin version. */
					__( 'by %1$s - %2$s', 'gravity' ),
					$author,
					$plugin['Version']
				)
			);

			$plugins[] = array(
				'label'         => $label,
				'value'         => $value,
			);
		}

		$group = new System_Report_Group();

		foreach ( $plugins as $plugin ) {
			$group->add( $plugin['label'], new System_Report_Item( $plugin['label'], $plugin['value'] ) );
		}

		return $group;
	}

	protected function get_web_server_data() {
		$items = array(
			array(
				'label'        => esc_html__( 'Software', 'gravity' ),
				'value'        => esc_html( $_SERVER['SERVER_SOFTWARE'] ),
			),
			array(
				'label'        => esc_html__( 'Port', 'gravity' ),
				'value'        => esc_html( $_SERVER['SERVER_PORT'] ),
			),
			array(
				'label'        => esc_html__( 'Document Root', 'gravity' ),
				'value'        => esc_html( $_SERVER['DOCUMENT_ROOT'] ),
			),
		);

		$group = new System_Report_Group();

		foreach ( $items as $item ) {
			$group->add( $item['label'], new System_Report_Item( $item['label'], $item['value'] ) );
		}

		return $group;
	}

	protected function get_php_data() {
		$curl_version = null;

		if ( function_exists( 'curl_version' ) ) {
			$curl_version_info = curl_version();

			if ( is_array( $curl_version_info ) && isset( $curl_version_info['version'] ) ) {
				$curl_version = $curl_version_info['version'];
			}
		}

		$items = array(
			array(
				'label'        => esc_html__( 'Version', 'gravity' ),
				'value'        => esc_html( phpversion() ),
			),
			array(
				'label'        => esc_html__( 'Memory Limit', 'gravity' ) . ' (memory_limit)',
				'value'        => esc_html( ini_get( 'memory_limit' ) ),
			),
			array(
				'label'        => esc_html__( 'Maximum Execution Time', 'gravity' ) . ' (max_execution_time)',
				'value'        => esc_html( ini_get( 'max_execution_time' ) ),
			),
			array(
				'label'        => esc_html__( 'Maximum File Upload Size', 'gravity' ) . ' (upload_max_filesize)',
				'value'        => esc_html( ini_get( 'upload_max_filesize' ) ),
			),
			array(
				'label'        => esc_html__( 'Maximum File Uploads', 'gravity' ) . ' (max_file_uploads)',
				'value'        => esc_html( ini_get( 'max_file_uploads' ) ),
			),
			array(
				'label'        => esc_html__( 'Maximum Post Size', 'gravity' ) . ' (post_max_size)',
				'value'        => esc_html( ini_get( 'post_max_size' ) ),
			),
			array(
				'label'        => esc_html__( 'Maximum Input Variables', 'gravity' ) . ' (max_input_vars)',
				'value'        => esc_html( ini_get( 'max_input_vars' ) ),
			),
			array(
				'label'        => esc_html__( 'cURL Enabled', 'gravity' ),
				'value'        => function_exists( 'curl_init' )
					? esc_html(
						sprintf(
							/* translators: %s: cURL version. */
							__( 'Yes (version %s)', 'gravity' ),
							$curl_version
						)
					)
					: esc_html__( 'No', 'gravity' ),
			),
			array(
				'label'        => esc_html__( 'OpenSSL', 'gravity' ),
				'value'        => defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT . ' (' . OPENSSL_VERSION_NUMBER . ')' : __( 'No', 'gravity' ),
			),
			array(
				'label'        => esc_html__( 'Mcrypt Enabled', 'gravity' ),
				'value'        => function_exists( 'mcrypt_encrypt' ) ? esc_html__( 'Yes', 'gravity' ) : esc_html__( 'No', 'gravity' ),
			),
			array(
				'label'        => esc_html__( 'Mbstring Enabled', 'gravity' ),
				'value'        => function_exists( 'mb_strlen' ) ? esc_html__( 'Yes', 'gravity' ) : esc_html__( 'No', 'gravity' ),
			),
			array(
				'label'        => esc_html__( 'Loaded Extensions', 'gravity' ),
				'value'        => join( ', ', get_loaded_extensions() ),
			),
		);

		$group = new System_Report_Group();

		foreach( $items as $item ) {
			$group->add( $item['label'], new System_Report_Item( $item['label'], $item['value'] ) );
		}

		return $group;
	}

	protected function get_database_server_data() {
		global $wpdb;

		$db_version = Common::get_db_version();
		$db_type    = Common::get_dbms_type();

		$items = array(
			array(
				'label'        => esc_html__( 'Database Management System', 'gravity' ),
				'value'        => esc_html( $db_type ),
			),
			array(
				'label'        => esc_html__( 'Version', 'gravity' ),
				'value'        => esc_html( $db_version ),
			),
			array(
				'label'        => esc_html__( 'Database Character Set', 'gravity' ),
				'value'        => esc_html( ( Common::get_dbms_type() === 'SQLite' ) ? $wpdb->charset : $wpdb->get_var( 'SELECT @@character_set_database' ) ),
			),
			array(
				'label'        => esc_html__( 'Database Collation', 'gravity' ),
				'value'        => esc_html( ( Common::get_dbms_type() === 'SQLite' ) ? ( empty( $wpdb->collate ) ? 'N/A' : $wpdb->collate ) : $wpdb->get_var( 'SELECT @@collation_database' ) ),
			),
		);

		$group = new System_Report_Group();

		foreach( $items as $item ) {
			$group->add( $item['label'], new System_Report_Item( $item['label'], $item['value'] ) );
		}

		return $group;
	}

	protected function get_date_and_time_data() {
		global $wpdb;

		$db_date  = $wpdb->get_var( 'SELECT utc_timestamp()' );
		$php_date = date( 'Y-m-d H:i:s' );

		$date_option = trim( get_option( 'date_format' ) );
		$time_option = trim( get_option( 'time_format' ) );
		$date_format = $date_option ? $date_option : 'Y-m-d';
		$time_format = $time_option ? $time_option : 'H:i';

		$gmt_db_date             = mysql2date( 'G', $db_date );
		$local_db_date           = strtotime( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $gmt_db_date ) ) );
		$formatted_local_db_date = sprintf(
			/* translators: 1: date, 2: time */
			__( '%1$s at %2$s', 'gravity' ),
			date_i18n( $date_format, $local_db_date, true ),
			date_i18n( $time_format, $local_db_date, true )
		);

		$gmt_php_date             = mysql2date( 'G', $php_date );
		$local_php_date           = strtotime( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $gmt_php_date ) ) );
		$formatted_local_php_date = sprintf(
			/* translators: 1: date, 2: time */
			__( '%1$s at %2$s', 'gravity' ),
			date_i18n( $date_format, $local_php_date, true ),
			date_i18n( $time_format, $local_php_date, true )
		);

		$items = array(
			array(
				'label'        => esc_html__( 'WordPress (Local) Timezone', 'gravity' ),
				'value'        => $this->get_timezone(),
			),
			array(
				'label'        => esc_html__( 'MySQL - Universal time (UTC)', 'gravity' ),
				'value'        => esc_html( $db_date ),
			),
			array(
				'label'        => esc_html__( 'MySQL - Local time', 'gravity' ),
				'value'        => esc_html( $formatted_local_db_date ),
			),
			array(
				'label'        => esc_html__( 'PHP - Universal time (UTC)', 'gravity' ),
				'value'        => esc_html( $php_date ),
			),
			array(
				'label'        => esc_html__( 'PHP - Local time', 'gravity' ),
				'value'        => esc_html( $formatted_local_php_date ),
			),
		);

		$group = new System_Report_Group();

		foreach( $items as $item ) {
			$group->add( $item['label'], new System_Report_Item( $item['label'], $item['value'] ) );
		}

		return $group;
	}

	protected function get_timezone() {
		$tzstring = get_option( 'timezone_string' );

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
			$tzstring = '';
		}

		if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists
			$current_offset = get_option( 'gmt_offset' );

			if ( 0 == $current_offset ) {
				$tzstring = 'UTC+0';
			} elseif ( $current_offset < 0 ) {
				$tzstring = 'UTC' . $current_offset;
			} else {
				$tzstring = 'UTC+' . $current_offset;
			}
		}

		return $tzstring;
	}
}
