<?php

use tad\FunctionMocker\FunctionMocker;

class WpLoader {
	public function init() {
		$this->setConstants();
		$this->preLoadWP();
		$this->loadWP();
		$this->postLoadWP();
		$this->requireTestCaseParents();
		$this->bootstrapMockAddon();
		$this->onShutdown();
	}

	protected function setConstants() {
		if ( ! defined( 'GMT_TESTS_DIR' ) ) {
			define( 'GMT_PLUGIN_DIR', __DIR__ . '/' );
			define( 'GMT_TESTS_DIR', GMT_PLUGIN_DIR . 'tests' );
			define( 'WP_TESTS_DIR', GMT_PLUGIN_DIR . 'vendor/wordpress/wordpress/tests/phpunit/' );
			define( 'WP_TESTS_CONFIG_FILE_PATH', GMT_PLUGIN_DIR . '/wp-tests-config.php' );
		}
	}


	protected function preLoadWP() {
		// if WordPress test suite isn't found then we can't do anything.
		if ( ! is_readable( WP_TESTS_DIR . 'includes/functions.php' ) ) {
			die( 'The WordPress PHPUnit test suite could not be found at: ' . WP_TESTS_DIR );
		}
		require_once WP_TESTS_DIR . 'includes/functions.php';
		// set filter for bootstrapping EE which needs to happen BEFORE loading WP.
		tests_add_filter( 'muplugins_loaded', array( $this, 'setupAndLoadWP' ) );
	}


	protected function loadWP() {
		FunctionMocker::setup();

		require WP_TESTS_DIR . 'includes/bootstrap.php';

		FunctionMocker::replace( 'wp_send_json_success', function( $text, $code = null, $flags = array() ) {
			global $hermes_test_response;
			var_dump( $text );
			$hermes_test_response = $text;
			return;
		} );
		FunctionMocker::replace( 'wp_send_json_error', function( $text, $code ) {
			global $hermes_test_response;
			$hermes_test_response = $text;
			return;
		} );
	}


	public function setupAndLoadWP() {
		if ( ! defined( 'SAVEQUERIES' ) ) {
			define( 'SAVEQUERIES', true );
		}
	}


	public function postLoadWP() {
		// ensure date and time formats are set
		if ( ! get_option( 'date_format' ) ) {
			update_option( 'date_format', 'F j, Y' );
		}
		if ( ! get_option( 'time_format' ) ) {
			update_option( 'time_format', 'g:i a' );
		}

		wp_set_current_user( 1 );
	}


	protected function requireTestCaseParents() {
		// good place to require any other files needed by tests, like mock files and test case parent files
	}


	protected function bootstrapMockAddon() {
		// good place to load any add-on files which we might want to also test
	}


	protected function onShutdown() {
		// nuke all PMB data once the tests are done, so that it doesn't carry over to the next time we run tests
		register_shutdown_function(
			function () {
				FunctionMocker::tearDown();

				\gravitytools_tests_reset_db();
			}
		);
	}

	// Entities ////////

	public function contact() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_contact';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		firstName varchar(100) NOT NULL,
		lastName varchar(100) NOT NULL,
		email varchar(100) NOT NULL,
		phone varchar(100) NOT NULL,
		profile_picture varchar(100) NOT NULL,
		dateCreated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		dateUpdated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		FULLTEXT(firstName),
		FULLTEXT(lastName),
		FULLTEXT(email),
		FULLTEXT(phone),
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}

	public function company() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_company';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		company_name varchar(100) NOT NULL,
		url varchar(100) NOT NULL,
		description text NOT NULL,
		dateCreated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		dateUpdated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		FULLTEXT(company_name),
		FULLTEXT(url),
		FULLTEXT(description),
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}

	public function group() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_group';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		label varchar(100) NOT NULL,
		dateCreated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		dateUpdated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		FULLTEXT(label),
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}

	public function deal() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_deal';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		label varchar(100) NOT NULL,
		status varchar(100) NOT NULL,
		dateCreated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		dateUpdated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		FULLTEXT(label),
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}

	public function pipeline() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_pipeline';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		label varchar(100) NOT NULL,
		dateCreated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		dateUpdated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		FULLTEXT(label),
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}

	// Relationships ////////

	public function company_to_contact() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_company_contact';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		company_id mediumint(9) NOT NULL,
		contact_id mediumint(9) NOT NULL,
		is_main tinyint(1) DEFAULT 0 NOT NULL,
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}

	public function group_to_contact() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_group_contact';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		group_id mediumint(9) NOT NULL,
		contact_id mediumint(9) NOT NULL,
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}

	public function deal_to_company() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_deal_company';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		deal_id mediumint(9) NOT NULL,
		company_id mediumint(9) NOT NULL,
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}

	public function deal_to_contact() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_deal_contact';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		deal_id mediumint(9) NOT NULL,
		contact_id mediumint(9) NOT NULL,
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}

	public function pipeline_to_deal() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_pipeline_deal';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		pipeline_id mediumint(9) NOT NULL,
		deal_id mediumint(9) NOT NULL,
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}

	/*//////// Meta ////////*/

	public function meta() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'gravitycrm_meta';

		$sql = "
		CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		object_type varchar(100) NOT NULL,
		object_id mediumint(9) NOT NULL,
		meta_name varchar(100) NOT NULL,
		meta_value mediumtext NOT NULL,
		FULLTEXT(meta_value),
		PRIMARY KEY (id)
		) $charset_collate;
		";

		dbDelta( $sql );
	}
}

