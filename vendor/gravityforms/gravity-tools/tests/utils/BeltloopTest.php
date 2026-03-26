<?php

namespace utils;

use Gravity_Forms\Gravity_Tools\Utils\Beltloop;
use PHPUnit\Framework\TestCase;

class BeltloopTest extends TestCase {

	public function testOrdering() {
		$data = array(
			array(
				'id'               => 7,
				'prevId'           => 6,
			),
			array(
				'id'               => 1,
				'prevId'           => 0,
			),
			array(
				'id'               => 3,
				'prevId'           => 2,
			),
			array(
				'id'               => 5,
				'prevId'           => 4,
			),
			array(
				'id'               => 6,
				'prevId'           => 5,
			),
			array(
				'id'               => 2,
				'prevId'           => 1,
			),
			array(
				'id'               => 4,
				'prevId'           => 3,
			),
		);

		$expected = array(
			array(
				'id'     => 1,
				'prevId' => 0,
			),
			array(
				'id'     => 2,
				'prevId' => 1,
			),
			array(
				'id'     => 3,
				'prevId' => 2,
			),
			array(
				'id'     => 4,
				'prevId' => 3,
			),
			array(
				'id'     => 5,
				'prevId' => 4,
			),
			array(
				'id'     => 6,
				'prevId' => 5,
			),
			array(
				'id'     => 7,
				'prevId' => 6,
			),
		);

		$sorted = Beltloop::sort( $data, 'id', 'prevId' );
		$this->assertEquals( $expected, $sorted );
	}

	public function testSortWithMissingItems() {
		$data = array(
			array(
				'id'               => 8,
				'prevId'           => 7,
			),
			array(
				'id'     => 9,
				'prevId' => 8,
			),
			array(
				'id'               => 3,
				'prevId'           => 2,
			),
			array(
				'id'               => 5,
				'prevId'           => 4,
			),
			array(
				'id'               => 7,
				'prevId'           => 6,
			),
			array(
				'id'               => 2,
				'prevId'           => 1,
			),
			array(
				'id'               => 1,
				'prevId'           => 0,
			),
			array(
				'id'               => 4,
				'prevId'           => 3,
			),
		);

		$expected = array(
			array(
				'id'     => 1,
				'prevId' => 0,
			),
			array(
				'id'     => 2,
				'prevId' => 1,
			),
			array(
				'id'     => 3,
				'prevId' => 2,
			),
			array(
				'id'     => 4,
				'prevId' => 3,
			),
			array(
				'id'     => 5,
				'prevId' => 4,
			),
			array(
				'id'     => 7,
				'prevId' => 6,
			),
			array(
				'id'     => 8,
				'prevId' => 7,
			),
			array(
				'id'     => 9,
				'prevId' => 8,
			),
		);

		$sorted = Beltloop::sort( $data );
		$this->assertEquals( $expected, $sorted );
	}

	public function testPartialSort() {
		$full_list = array(
			array(
				'id'     => 1,
				'prevId' => 0,
			),
			array(
				'id'     => 2,
				'prevId' => 1,
			),
			array(
				'id'     => 3,
				'prevId' => 2,
			),
			array(
				'id'     => 4,
				'prevId' => 3,
			),
			array(
				'id'     => 5,
				'prevId' => 4,
			),
			array(
				'id'     => 6,
				'prevId' => 5,
			),
			array(
				'id'     => 7,
				'prevId' => 6,
			),
		);

		$partial_list = array(
			array(
				'id'     => 7,
				'prevId' => 6,
			),
			array(
				'id'     => 4,
				'prevId' => 3,
			),
			array(
				'id'     => 1,
				'prevId' => 0,
			),
			array(
				'id'     => 5,
				'prevId' => 4,
			),
		);

		$expected = array(
			array(
				'id'     => 1,
				'prevId' => 0,
			),
			array(
				'id'     => 4,
				'prevId' => 3,
			),
			array(
				'id'     => 5,
				'prevId' => 4,
			),
			array(
				'id'     => 7,
				'prevId' => 6,
			),
		);

		$result = Beltloop::partial_sort( $full_list, $partial_list );

		$this->assertEquals( $expected, $result );
	}
}
