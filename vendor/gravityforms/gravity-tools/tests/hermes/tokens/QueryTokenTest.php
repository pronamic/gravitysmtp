<?php

namespace hermes\tokens;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Query_Token;
use PHPUnit\Framework\TestCase;

class QueryTokenTest extends TestCase {

	public function testQueryParsesToArray() {
		$text = file_get_contents( dirname( __FILE__ ) . '/../../_data/basic.graphql' );
		$data = new Query_Token( $text );

		$items = $data->items();
		$this->assertCount( 1, $items );

		$heroFields = $items[0]->fields();
		$this->assertCount( 3, $heroFields );

		$friendsFields = $heroFields[2]->fields();
		$this->assertCount( 3, $friendsFields );

		$secondaryFriendFields = $friendsFields[2]->fields();
		$this->assertCount( 3, $secondaryFriendFields );

		$tertiaryFriendFields = $secondaryFriendFields[2]->fields();
		$this->assertcount( 2, $tertiaryFriendFields );

		$this->assertEquals( $heroFields[1]->object_type(), 'height' );
		$this->assertEquals( $heroFields[1]->alias(), 'metricHeight' );
		$this->assertCount( 1, $heroFields[1]->arguments()->items() );
	}

}