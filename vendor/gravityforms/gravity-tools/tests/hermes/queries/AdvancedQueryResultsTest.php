<?php

namespace hermes\queries;

use FakeDealModel;
use FakeStageModel;
use FakeUserModel;
use Gravity_Forms\Gravity_Tools\Hermes\Query_Handler;
use Gravity_Forms\Gravity_Tools\Hermes\Runners\Schema_Runner;
use Gravity_Forms\Gravity_Tools\Hermes\Utils\Model_Collection;
use PHPUnit\Framework\TestCase;

class AdvancedQueryResultsTest extends TestCase {

	protected $model_collection;
	protected $query_handler;
	protected $db_namespace;
	protected $deal_model;
	protected $stage_model;
	protected $user_model;

	public function setUp(): void {
		global $wpdb;
		\gravitytools_tests_reset_db();
		$this->model_collection = new Model_Collection();
		$this->deal_model       = new FakeDealModel();
		$this->stage_model      = new FakeStageModel();
		$this->user_model = new FakeUserModel();

		$this->model_collection->add( 'deal', $this->deal_model );
		$this->model_collection->add( 'stage', $this->stage_model );
		$this->model_collection->add( 'user', $this->user_model );
		$this->db_namespace = 'gravitycrm';

		$schema_runner       = new Schema_Runner( $this->model_collection );
		$this->query_handler = new Query_Handler( $this->db_namespace, $this->model_collection, $schema_runner );

		$wpdb->query("INSERT INTO `wp_gravitycrm_stage` (`label`, `dateCreated`, `dateUpdated`)
			VALUES ('New', '0000-00-00 00:00:00', '0000-00-00 00:00:00');");

		$wpdb->query("INSERT INTO `wp_gravitycrm_stage` (`label`, `dateCreated`, `dateUpdated`)
			VALUES ('Won', '0000-00-00 00:00:00', '0000-00-00 00:00:00');");

		$wpdb->query("INSERT INTO `wp_gravitycrm_stage` (`label`, `dateCreated`, `dateUpdated`)
			VALUES ('Lost', '0000-00-00 00:00:00', '0000-00-00 00:00:00');");

		$wpdb->query("INSERT INTO `wp_gravitycrm_deal` (`label`, `source`, `value`, `estimatedCloseDate`, `notes`, `attachments`, `dateCreated`, `dateUpdated`, `pipelineId`, `stageId`, `userId`)
VALUES ('Great Deal 2', 'imported', '1000000', '0000-00-00 00:00:00', NULL, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '1', '2', '1');");

		$wpdb->query("INSERT INTO `wp_gravitycrm_deal` (`label`, `source`, `value`, `estimatedCloseDate`, `notes`, `attachments`, `dateCreated`, `dateUpdated`, `pipelineId`, `stageId`, `userId`)
VALUES ('Great Deal 3', 'imported', '1000000', '0000-00-00 00:00:00', NULL, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '1', '3', '1');");
	}

	public function testOTMRelationships() {
		$query = '{
			deal {
				id,
				label,
				stage {
					label,
				}
			}
		}';

		$result = $this->query_handler->handle_query( $query, true );
		$this->assertEquals( 2, count( $result['deal'] ) );

		$this->assertEquals( 'Won', $result['deal'][0]['stage'][0]['label']);
		$this->assertEquals( 'Lost', $result['deal'][1]['stage'][0]['label']);
	}

	public function testCustomTableQueries() {
		$query = '{
			user {
				ID,
				user_nicename,
				user_login,
			}
		}';

		$result = $this->query_handler->handle_query( $query, true );

		$this->assertEquals( 1, count( $result['user'] ) );
		$this->assertEquals( 'admin', $result['user'][0]['user_login'] );
	}

	public function testCustomTableQueriesWithRelationships() {
		$query = '{
			deal {
				id,
				label,
				user {
					ID,
					user_login
				}
			}
		}';

		$result = $this->query_handler->handle_query( $query, true );

		$this->assertEquals( 2, count( $result['deal' ] ) );
		$this->assertEquals( 'admin', $result['deal'][0]['user'][0]['user_login'] );

		$query = '{
			user {
				ID,
				user_nicename,
				deal {
					id,
					label,
				}
			}
		}';

		$result = $this->query_handler->handle_query( $query, true );

		$this->assertEquals( 2, count( $result['user'][0]['deal'] ) );
	}
}
