<?php

namespace utils;

use Exception;
use Gravity_Forms\Gravity_Tools\Utils\Bettarray;
use PHPUnit\Framework\TestCase;

class BettarrayTest extends TestCase {

	public function testArrayOps() {
		$init_data = array( 'foo' => 'bar', 'bing' => 'bash' );
		$data = new Bettarray( $init_data );

		unset( $data['foo'] );

		$this->assertEquals( array( 'bing' => 'bash' ), $data->all() );

		$data['bingo'] = 'bango';

		$this->assertEquals( array( 'bing' => 'bash', 'bingo' => 'bango' ), $data->all() );

		$data[] = 'huzzah';

		$this->assertEquals( array( 'bing' => 'bash', 'bingo' => 'bango', 0 => 'huzzah' ), $data->all() );
	}

	public function testSet() {
		$data = new Bettarray( array() );

		$data->set( 'foo', 'bar' );

		$this->assertEquals( array( 'foo' => 'bar' ), $data->all() );
	}

	public function testGet() {
		$data = new Bettarray( array( 'foo' => 'bar', 'bing' => 'bash' ) );

		$this->assertEquals( 'bar', $data->get( 'foo' ) );
		$this->assertEquals( 'bash', $data->get( 'bing' ) );

		$data->set( 'bingo', 'bango' );

		$this->assertEquals( 'bango', $data->get( 'bingo' ) );
	}

	public function testDelete() {
		$data = new Bettarray( array( 'foo' => 'bar' ) );
		$data->delete( 'foo' );

		$this->assertEquals( array(), $data->all() );
	}

	public function testDotSet() {
		$data = new Bettarray( array( 'foo' => 'bar' ) );

		$data->set( 'bing.bash.bazinga', array( 1, 2, 3 ) );

		$this->assertEquals( array( 'foo' => 'bar', 'bing' => array( 'bash' => array( 'bazinga' => array( 1, 2, 3 ) ) ) ), $data->all() );
	}

	public function testDotGet() {
		$data = new Bettarray( array( 'foo' => array( 'bar' => array( 'bash' => 'found' ) ) ) );

		$value = $data->get( 'foo.bar.bash' );

		$this->assertEquals( 'found', $value );
	}

	public function testDotDelete() {
		$data = new Bettarray( array( 'foo' => array( 'bar' => array( 'bash' => 'hey' ) ) ) );
		$data->delete( 'foo.bar.bash' );

		$this->assertEquals( array( 'foo' => array( 'bar' => array() ) ), $data->all() );
	}

	public function testAppend() {
		$data = new Bettarray( array( 'foo' => array( 1, 2, 3 ) ) );
		$data->append( 'foo', 4 );

		$this->assertEquals( array( 'foo' => array( 1, 2, 3, 4 ) ), $data->all() );
	}

	public function testDotAppend() {
		$data = new Bettarray( array( 'foo' => array( 'bar' => array( 1, 2, 3 ) ) ) );
		$data->append( 'foo.bar', 4 );

		$this->assertEquals( array( 'foo' => array( 'bar' => array( 1, 2, 3, 4, ) ) ), $data->all() );
	}

	public function testAmend() {
		$data = new Bettarray( array( 'foo' => array( 'bar' => 'bash' ) ) );
		$data->amend( 'foo', array( 'bingo' => 'bango' ) );

		$this->assertEquals( array( 'foo' => array( 'bar' => 'bash', 'bingo' => 'bango' ) ), $data->all() );
	}

	public function testDotAmend() {
		$data = new Bettarray( array( 'foo' => array( 'bar' => array( 'bingo' => 'bango' ) ) ) );
		$data->amend( 'foo.bar', array( 'bazinga' => 'bazanga' ) );

		$this->assertEquals( array( 'foo' => array( 'bar' => array( 'bingo' => 'bango', 'bazinga' => 'bazanga' ) ) ), $data->all() );
	}

	public function testSlice() {
		$data = new Bettarray( array( 1, 2, 3, 4, 5, 6, 7 ) );

		$new_data = $data->slice( 2, 3 );

		$this->assertEquals( array( 3, 4, 5 ), $new_data->all() );
	}

	public function testCount() {
		$data = new Bettarray( array( 1, 2, 3, 4, 5 ) );

		$this->assertEquals( $data->count(), 5 );

		$data = new Bettarray( array( 'foo', 'bar', 'bing', 'bash' ) );

		$this->assertEquals( 4, $data->count() );
	}

	public function testPluck() {
		$data = new Bettarray( array( array( 'foo' => 'bar', 'name' => 'Aaron' ), array( 'foo' => 'bar', 'name' => 'Ryan' ) ) );

		$names = $data->pluck( 'name' );

		$this->assertEquals( array( 'Aaron', 'Ryan' ), $names->all() );
	}

	public function testFilter() {
		$data = new Bettarray( array( array( 'id' => 1 ), array( 'id' => 2 ), array( 'id' => 3 ), array( 'id' => 4 ) ) );

		$filtered = $data->filter( function( $item ) {
			return $item['id'] > 2;
		} );

		$this->assertEquals( 2, $filtered->count() );
	}
}
