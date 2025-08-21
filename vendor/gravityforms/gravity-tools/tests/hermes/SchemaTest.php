<?php

use Gravity_Forms\Gravity_Tools\Hermes\Query_Handler;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Schema_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Utils\Model_Collection;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase {

	protected $model_collection;
	protected $contact_model;
	protected $company_model;
	protected $email_model;
	protected $website_model;

	/**
	 * @var Query_Handler
	 */
	protected $query_handler;
	protected $phone_model;
	protected $db_namespace;

	public function setUp() {
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

	public function testSchemaQueries() {
		$text = '{ __schema(){ name, fields( type: "INT" ) { name, type }, metaFields { name, type }, relationships { to, accessCap } } }';

		$this->query_handler->handle_query( $text );
	}
}
