<?php

namespace hermes\tokens\mutations;

use Gravity_Forms\Gravity_Tools\Hermes\Tokens\Mutations\Insert\Insert_Mutation_Token;
use PHPUnit\Framework\TestCase;

class QueryTokenTest extends TestCase {

	public function testMutationParsesToArray() {
		$text  = '{
  insert_todos(objects: [{title: "New Todo", status: "active"}, {title: "Second Todo", status: "pending"}]) {
    returning {
      id
      title
      is_completed
      is_public
      created_at
    }
  }
}';
		$token = new Insert_Mutation_Token( $text );

		$expected_fields = array(
			"id",
			"title",
			"is_completed",
			"is_public",
			"created_at",
		);

        $this->assertEquals( $expected_fields, $token->return_fields() );
	}

	public function testInsertObjectsParseCorrectly(){
		$text = '{
  insert_todos(objects: [{title: "New Todo", status: "active", body: "Hey this is the body. Its a lot of fun, and has \"nested\" quotation marks."}, {title: "Second Todo", status: "pending"}]) {
    returning {
      id
      title
      is_completed
      is_public
      created_at
    }
  }
}';
		$token = new Insert_Mutation_Token( $text );

		$this->assertEquals( 2, count( $token->children()->children() ) );

		$expected_fields = array(
			"id",
			"title",
			"is_completed",
			"is_public",
			"created_at",
		);

		$this->assertEquals( $expected_fields, $token->return_fields() );
	}

	public function testInsertedObjectsWithComplexFields(){
		$text = '{
  insert_todos(objects: [{title: "New Todo", status: "active", body: "<html>
 <body>
 <p>Hey this is some text. It has <b>bold text</b> and some other <br/> things in it.</p>
 </body>
</html>"}, {title: "Second Todo", status: "pending"}]) {
    returning {
      id
      title
      is_completed
      is_public
      created_at
    }
  }
}';
		$token = new Insert_Mutation_Token( $text );

		$this->assertEquals( 2, count( $token->children()->children() ) );

		$expected_fields = array(
			"id",
			"title",
			"is_completed",
			"is_public",
			"created_at",
		);

		$this->assertEquals( $expected_fields, $token->return_fields() );
	}

	public function testInsertedObjectsWithRelationships() {
		$text = '{
		insert_company( objects: [
			{
				companyName: "Acme, INC",
				contact: [
					{
						firstName: "John",
						lastName: "Smith",
						email: [{
							type: "work",
							address: "jsmith@acme.local",
						}]
					},
					{
						firstName: "Jane",
						lastName: "Doe",
					}
				]	
			},
			{
				companyName: "Acme2, INC",
				contact: [
					{
						firstName: "Phil",
						lastName: "Johnson"
					},
					{
						firstName: "Janet",
						lastName: "Bigelow",
					}
				]
			}
		]){
			returning {
				id,
				companyName,
				contact: {
					id,
					firstName,
					lastName,
					email {
						id,
						address,
					}	
				}
			}
		}
		}';

		$token = new Insert_Mutation_Token( $text );

		$objects = $token->children()->children();

		$this->assertEquals( 7, count( $objects ) );
	}
}
