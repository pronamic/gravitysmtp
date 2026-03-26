<?php

namespace hermes\queries;

use Gravity_Forms\Gravity_Tools\Hermes\Mutation_Handler;
use Gravity_Forms\Gravity_Tools\Hermes\Query_Handler;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Connect_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Delete_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Insert_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Schema_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Update_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Utils\Model_Collection;
use PHPUnit\Framework\TestCase;

global $wpdb;

class QueryResultsTest extends TestCase {

	protected $model_collection;
	protected $contact_model;
	protected $company_model;
	protected $email_model;
	protected $website_model;
	protected $query_handler;
	protected $phone_model;
	protected $db_namespace;

	public static function setUpBeforeClass(): void {
		gravitytools_tests_reset_db();
		$sql = file_get_contents( dirname( __FILE__ ) . '/../../_data/prepopulated.sql' );

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		\dbDelta( $sql );
	}

	public function setUp(): void {
		$this->model_collection = new Model_Collection();
		$this->contact_model    = new \FakeContactModel();
		$this->company_model    = new \FakeCompanyModel();
		$this->phone_model      = new \FakePhoneModel();
		$this->email_model      = new \FakeEmailModel();
		$this->website_model    = new \FakeWebsiteModel();

		$this->model_collection->add( 'contact', $this->contact_model );
		$this->model_collection->add( 'company', $this->company_model );
		$this->model_collection->add( 'email', $this->email_model );
		$this->model_collection->add( 'phone', $this->phone_model );
		$this->model_collection->add( 'website', $this->website_model );
		$this->db_namespace = 'gravitycrm';

		$schema_runner = new Schema_Runner( $this->model_collection );
		$this->query_handler = new Query_Handler( $this->db_namespace, $this->model_collection, $schema_runner );
	}

	// Single item type query
	public function testSingleItemQuery() {
		$query = "
			{
				contact() {
					id,
					firstName,
					lastName
       }
			}
";

		$result = $this->query_handler->handle_query( $query, true );

		$this->assertEquals( 3, count( $result['contact'] ) );
	}

	public function testSingleItemQueryWithAliases() {
		$query = "
			{
				contact() {
					contactId: id,
					firstName,
					lastName
       }
			}
";

		$result = $this->query_handler->handle_query( $query, true );

		$this->assertEquals( 3, count( $result['contact'] ) );
	}

	// Multiple item type query
	public function testMultiItemQuery() {
		$query = "
			{
				contact() {
					id,
					firstName,
					lastName
			 },
			 company() {
					id,
					companyName,
       }
		}
";

		$result = $this->query_handler->handle_query( $query, true );

		$this->assertEquals( 3, count( $result['contact'] ) );
		$this->assertEquals( 2, count( $result['company'] ) );
	}
	// Nested query with M2M
	// Nested query with O2M
	// Query with transformations
	public function testQueryWithTransformations() {
		$query = "
		{
			contact() {
				id,
				firstName,
				lastName,
				fakeThumb: id( transformMakeThumb: 'square|large' ),
				fakeThumbDefaults: id( transformMakeThumb: '' ),
				fakeThumbSingle: id( transformMakeThumb: 'rectangle' ),
			}
		}
";

		$result = $this->query_handler->handle_query( $query, true );

		$this->assertEquals( 'thumbnail_url:1/square/large', $result['contact'][0]['fakeThumb']);
		$this->assertEquals( 'thumbnail_url:1/hexagon/medium', $result['contact'][0]['fakeThumbDefaults']);
		$this->assertEquals( 'thumbnail_url:1/rectangle/medium', $result['contact'][0]['fakeThumbSingle']);
	}
}
