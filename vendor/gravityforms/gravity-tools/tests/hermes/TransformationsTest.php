<?php

namespace hermes\tokens;

use Gravity_Forms\Gravity_Tools\Hermes\Query_Handler;
use Gravity_Forms\Gravity_Tools\Hermes\Utils\Model_Collection;
use PHPUnit\Framework\TestCase;

class TransformationsTest extends TestCase {

	protected $model_collection;
	protected $contact_model;
	protected $company_model;
	protected $query_handler;
	protected $db_namespace;

	public function setUp() {
		$this->model_collection = new Model_Collection();
		$this->contact_model    = new \FakeContactModel();
		$this->company_model      = new \FakeCompanyModel();

		$this->model_collection->add( 'contact', $this->contact_model );
		$this->model_collection->add( 'company', $this->company_model );

		$this->db_namespace = 'gravitycrm';

		$this->query_handler = new Query_Handler( $this->db_namespace, $this->model_collection );
	}

	public function testTransformationsAreAppliedInQueries() {
		$query_text = '
		{
			company {
				id,
				companyName,
				contact {
					id,
					main_img: profile_picture(transformMakeThumb: lg),
				}
			}
		}
		';

		$handled = $this->query_handler->handle_query( $query_text );
	}

}


