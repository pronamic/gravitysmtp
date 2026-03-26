<?php

namespace hermes\queries;

use Exception;
use FakeCompanyModel;
use FakeContactModel;
use FakeDealModel;
use FakeEmailModel;
use FakePhoneModel;
use FakeStageModel;
use FakeWebsiteModel;
use Gravity_Forms\Gravity_Tools\Hermes\Mutation_Handler;
use Gravity_Forms\Gravity_Tools\Hermes\Query_Handler;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Connect_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Delete_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Disconnect_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Insert_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Schema_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Update_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Utils\Model_Collection;
use PHPUnit\Framework\TestCase;

global $wpdb;

class MutationResultsTest extends TestCase {

	protected $model_collection;
	protected $contact_model;
	protected $company_model;
	protected $email_model;
	protected $website_model;
	protected $query_handler;
	protected $phone_model;
	protected $mutation_handler;
	protected $db_namespace;
	protected $deal_model;
	protected $stage_model;

	public function setUp(): void {
		$this->model_collection = new Model_Collection();
		$this->contact_model    = new FakeContactModel();
		$this->company_model    = new FakeCompanyModel();
		$this->phone_model      = new FakePhoneModel();
		$this->email_model      = new FakeEmailModel();
		$this->website_model    = new FakeWebsiteModel();
		$this->deal_model       = new FakeDealModel();
		$this->stage_model      = new FakeStageModel();

		$this->model_collection->add( 'contact', $this->contact_model );
		$this->model_collection->add( 'company', $this->company_model );
		$this->model_collection->add( 'email', $this->email_model );
		$this->model_collection->add( 'phone', $this->phone_model );
		$this->model_collection->add( 'website', $this->website_model );
		$this->model_collection->add( 'deal', $this->deal_model );
		$this->model_collection->add( 'stage', $this->stage_model );
		$this->db_namespace = 'gravitycrm';

		$schema_runner = new Schema_Runner( $this->model_collection );

		$this->query_handler = new Query_Handler( $this->db_namespace, $this->model_collection, $schema_runner );

		$connect_runner = new Connect_Runner( $this->db_namespace, $this->query_handler, $this->model_collection );

		$runners = array(
			'insert'     => new Insert_Runner( $this->db_namespace, $this->query_handler, $this->model_collection, $connect_runner ),
			'delete'     => new Delete_Runner( $this->db_namespace, $this->query_handler, $this->model_collection ),
			'connect'    => $connect_runner,
			'disconnect' => new Disconnect_Runner( $this->db_namespace, $this->query_handler, $this->model_collection ),
			'update'     => new Update_Runner( $this->db_namespace, $this->query_handler, $this->model_collection ),
		);

		$this->mutation_handler = new Mutation_Handler( $this->db_namespace, $this->model_collection, $this->query_handler, $runners );
	}

	public function testItemsAddedWithOtmRelationships() {
		$text = '{
  insert_deal(
    objects: [
      {
        label: "Cool Deal"
				stage: [{
					label: "New",
				}]
      }
    ]
  ) {
    returning {
			id,
			label,
			stage {
				id,
		}
    }
	}
}';

		try {
			$data = $this->mutation_handler->handle_mutation( $text, true );
		} catch ( Exception $e ) {
			$this->assertEquals( 'success', $e->getMessage() );

			return;
		}

		$this->assertEquals( 1, count( $data['deal'] ) );
		$this->assertEquals( 1, count( $data['deal'][0]['stage'] ) );
	}

	public function testItemsAddedWithRelationships() {
		$text = '{
  insert_company(
    objects: [
      {
        companyName: "Test Company"
        email: [
          { type: "work", address: "testcompany@abc.com" }
          { type: "work", address: "test@company.com" }
        ]
        phone: [{ type: "work", number: "12223334444", countryCode: "US" }]
        website: [{ type: "work", url: "testcompany.com" }]
      }
    ]
  ) {
    returning {
		id,
		companyName,
      email {
		id,
		address,
		type,
      }
      phone {
		id,
		number,
		type,
      }
      website {
		id,
		url,
		type
      }
    }
  }
}';

		try {
			$data = $this->mutation_handler->handle_mutation( $text, true );
		} catch ( Exception $e ) {
			$this->assertEquals( 'success', $e->getMessage() );

			return;
		}

		$this->assertEquals( 1, count( $data['company'] ) );
		$this->assertEquals( 2, count( $data['company'][0]['email'] ) );
		$this->assertEquals( 1, count( $data['company'][0]['phone'] ) );
		$this->assertEquals( 1, count( $data['company'][0]['website'] ) );
	}
}
