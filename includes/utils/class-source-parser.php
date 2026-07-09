<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

/**
 * Source_Parser
 *
 * Takes the trace from a debug_backtrace() call and parses it to find the source
 * of a wp_mail() call (if present).
 */
class Source_Parser {

	protected $subtype;

	public function get_subtype() {
		return $this->subtype;
	}

	/**
	 * Get the source of a wp_mail call from a given trace.
	 *
	 * @since 1.0
	 *
	 * @param array $trace
	 *
	 * @return string
	 */
	public function get_source_from_trace( $trace, $field = 'name' ) {
		$relevant_trace = $this->get_relevant_trace_data( $trace );
		$non_result     = $field === 'all' ? array( 'slug' => 'na', 'name' => __( 'N/A', 'gravitysmtp' ), 'subtype' => 'na' ) : __( 'N/A', 'gravitysmtp' );

		if ( ! $relevant_trace ) {
			return $non_result;
		}

		$file_path = $relevant_trace['file'];

		$data = $this->get_source_from_file_path( $file_path );

		if ( $data === false ) {
			return $non_result;
		}

		if ( $field === 'all' ) {
			return $data;
		}

		return isset( $data[ $field ] ) ? $data[ $field ] : $non_result;
	}

	/**
	 * Get the relevant wp_mail trace data from a given trace array.
	 *
	 * @since 1.0
	 *
	 * @param array $trace
	 *
	 * @return bool|array
	 */
	protected function get_relevant_trace_data( $trace ) {
		$filtered = array_filter( $trace, function ( $item ) {
			return $item['function'] === 'wp_mail';
		} );

		if ( empty( $filtered ) ) {
			return false;
		}

		return reset( $filtered );
	}

	/**
	 * For a given file path, determine the source of the call (WordPress core, a theme, a plugin/mu-plugin, or N/A if not found)
	 *
	 * @since 1.0
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function get_source_from_file_path( $path ) {
		$core = $this->get_core_source( $path );

		if ( $core ) {
			$this->subtype = 'core';

			return $core;
		}

		$theme = $this->get_theme_source( $path );

		if ( $theme ) {
			$this->subtype = 'theme';

			return $theme;
		}

		$plugin = $this->get_plugin_source( $path );

		if ( $plugin ) {
			$this->subtype = 'plugin';

			return $plugin;
		}

		$mu_plugin = $this->get_mu_plugin_source( $path );

		if ( $mu_plugin ) {
			$this->subtype = 'mu_plugin';

			return $mu_plugin;
		}

		$this->subtype = '';

		return array(
			'name'    => __( 'N/A', 'gravitysmtp' ),
			'slug'    => '',
			'subtype' => '',
		);
	}

	/**
	 * Determine if WordPress Core was the source.
	 *
	 * @since 1.0
	 *
	 * @param string $path
	 *
	 * @return string|bool
	 */
	protected function get_core_source( $path ) {
		if (
			strpos( $path, 'wp-admin' ) !== false ||
			strpos( $path, 'wp-includes' ) !== false
		) {
			return array(
				'name'    => __( 'WordPress', 'gravitysmtp' ),
				'slug'    => 'wordpress',
				'subtype' => 'core',
			);
		}

		return false;
	}

	/**
	 * Determine if a plugin was the source.
	 *
	 * @since 1.0
	 *
	 * @param string $path
	 *
	 * @return string|bool
	 */
	protected function get_plugin_source( $path ) {
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return false;
		}

		$root      = basename( WP_PLUGIN_DIR );
		$separator = defined( 'DIRECTORY_SEPARATOR' ) ? '\\' . DIRECTORY_SEPARATOR : '\/';

		preg_match( "/$separator$root$separator(.[^$separator]+)($separator|\.php)/", $path, $result );

		if ( ! empty( $result[1] ) ) {
			$all_plugins = \get_plugins();
			$plugin_slug = $result[1];

			$filtered = array_filter( $all_plugins, function ( $plugin_data, $plugin ) use ( $plugin_slug ) {
				return 1 === preg_match( "/^$plugin_slug(\/|\.php)/", $plugin ) && isset( $plugin_data['Name'] );
			}, ARRAY_FILTER_USE_BOTH );

			if ( ! empty( $filtered ) ) {
				$found = reset( $filtered );

				return array(
					'slug'    => $result[1],
					'name'    => $found['Name'],
					'subtype' => 'plugin',
				);
			}

			return array(
				'slug'    => $result[1],
				'name'    => $result[1],
				'subtype' => 'plugin',
			);
		}

		return false;
	}

	/**
	 * Determine if an MU-plugin was the source.
	 *
	 * @since 1.0
	 *
	 * @param string $path
	 *
	 * @return string|bool
	 */
	protected function get_mu_plugin_source( $path ) {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return false;
		}

		$root      = basename( WPMU_PLUGIN_DIR );
		$separator = defined( 'DIRECTORY_SEPARATOR' ) ? '\\' . DIRECTORY_SEPARATOR : '\/';

		preg_match( "/$separator$root$separator(.[^$separator]+)($separator|\.php)/", $path, $result );

		if ( ! empty( $result[1] ) ) {
			return array(
				'name'    => __( 'MU Plugin', 'gravitysmtp' ),
				'slug'    => $result[1],
				'subtype' => 'mu_plugin',
			);
		}

		return false;
	}

	/**
	 * Determine if a theme was the source.
	 *
	 * @since 1.0
	 *
	 * @param string $path
	 *
	 * @return string|bool
	 */
	protected function get_theme_source( $path ) {
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			return false;
		}

		$root      = basename( WP_CONTENT_DIR );
		$separator = defined( 'DIRECTORY_SEPARATOR' ) ? '\\' . DIRECTORY_SEPARATOR : '\/';

		preg_match( "/$separator$root{$separator}themes{$separator}(.[^$separator]+)/", $path, $result );

		if ( ! empty( $result[1] ) ) {
			$theme = \wp_get_theme( $result[1] );

			if ( ! method_exists( $theme, 'get' ) ) {
				return array(
					'name'    => $result[1],
					'slug'    => $result[1],
					'subtype' => 'theme',
				);
			}

			return array(
				'name'    => $theme->get( 'Name' ),
				'slug'    => $result[1],
				'subtype' => 'theme',
			);
		}

		return false;
	}
}
