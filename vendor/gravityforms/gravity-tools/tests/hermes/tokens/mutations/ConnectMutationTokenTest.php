<?php

namespace hermes\tokens\mutations;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Connect\Connection_Values_Token;
use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Insert\Insert_Mutation_Token;
use PHPUnit\Framework\TestCase;

class ConnectMutationTokenTest extends TestCase {

	public function testArgumentsParseCorrectly() {
		$text  = '{
  connect_company_contact([{from: 1, to: 2}, {from:1, to: 3}, {from: 1, to: 4}]) {}
}';
		$token = new Connection_Values_Token( $text );

		$pairs = $token->children();

		$this->assertEquals( 3, count( $pairs ) );

		$this->assertEquals( 1, $pairs[0]['from'] );
		$this->assertEquals( 1, $pairs[1]['from'] );
		$this->assertEquals( 1, $pairs[2]['from'] );


		$this->assertEquals( 2, $pairs[0]['to'] );
		$this->assertEquals( 3, $pairs[1]['to'] );
		$this->assertEquals( 4, $pairs[2]['to'] );
	}


	public function testSingleArgumentParsesCorrectly() {
		$text  = '{
  connect_company_contact({from: 1, to: 2}) {}
}';
		$token = new Connection_Values_Token( $text );

		$pairs = $token->children();

		$this->assertEquals( 1, count( $pairs ) );

		$this->assertEquals( 1, $pairs[0]['from'] );

		$this->assertEquals( 2, $pairs[0]['to'] );
	}
}
