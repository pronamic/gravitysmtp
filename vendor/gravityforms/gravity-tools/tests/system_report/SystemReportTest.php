<?php

namespace system_report;

use Gravity_Forms\Gravity_Tools\System_Report\System_Report_Group;
use Gravity_Forms\Gravity_Tools\System_Report\System_Report_Item;
use Gravity_Forms\Gravity_Tools\System_Report\System_Report_Repository;
use PHPUnit\Framework\TestCase;

class SystemReportTest extends TestCase {

	public function testAddGroup() {
		$repo  = System_Report_Repository::instance( true );
		$group = new System_Report_Group();

		$repo->add( 'foobar', $group );

		$retrieve = $repo->get( 'foobar' );

		$this->assertEquals( $group, $retrieve );
	}

	public function testHasGroup() {
		$repo  = System_Report_Repository::instance( true );
		$group = new System_Report_Group();
		$repo->add( 'foobar', $group );
		$this->assertTrue( $repo->has( 'foobar' ) );
	}

	public function testDeleteGroup() {
		$repo  = System_Report_Repository::instance( true );
		$group = new System_Report_Group();
		$repo->add( 'foobar', $group );
		$this->assertTrue( $repo->has( 'foobar' ) );

		$repo->delete( 'foobar' );

		$this->assertFalse( $repo->has( 'foobar' ) );
	}

	public function testAddItem() {
		$group = new System_Report_Group();
		$item  = new System_Report_Item( 'foo', 'bar' );

		$group->add( 'foobar', $item );

		$retrieved = $group->get( 'foobar' );

		$this->assertEquals( $retrieved, $item );
	}

	public function testHasItem() {
		$group = new System_Report_Group();
		$item  = new System_Report_Item( 'foo', 'bar' );

		$group->add( 'foobar', $item );

		$this->assertTrue( $group->has( 'foobar' ) );
	}

	public function testDeleteItem() {
		$group = new System_Report_Group();
		$item  = new System_Report_Item( 'foo', 'bar' );

		$group->add( 'foobar', $item );

		$this->assertTrue( $group->has( 'foobar' ) );

		$group->delete( 'foobar' );

		$this->assertFalse( $group->has( 'foobar' ) );
	}

	public function testItemEscape() {
		$item = new System_Report_Item( '<script>hey<script><a>foo</a>', '<a>bar</a>' );

		$this->assertEquals( 'hey<a>foo</a>', $item->key() );
		$this->assertEquals( '<a>bar</a>', $item->value() );
	}

	public function testItemSensitive() {
		$item = new System_Report_Item( 'foo', 'bar', true );

		$this->assertEquals( 'foo', $item->key() );
		$this->assertEquals( '**********', $item->value() );
	}

	public function testAsArray() {
		$repo  = System_Report_Repository::instance( true );
		$group = new System_Report_Group();
		$item  = new System_Report_Item( 'foo', 'bar' );
		$group->add( 'foobing', $item );

		$item = new System_Report_Item( 'foo', 'bash', true );
		$group->add( 'foobash', $item );

		$repo->add( 'foobar', $group );

		$expected = array(
			'foobar' => array(
				'foobing' => array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				'foobash' => array(
					'key'   => 'foo',
					'value' => '**********',
				),
			),
		);

		$this->assertEquals( $expected, $repo->as_array() );
	}

	public function testAsString() {
		$repo  = System_Report_Repository::instance( true );
		$group = new System_Report_Group();
		$item  = new System_Report_Item( 'Active Theme', 'Twenty Twenty-One' );
		$group->add( 'active_theme', $item );

		$item = new System_Report_Item( 'Secret Key', 'Bash', true );
		$group->add( 'secret_key', $item );

		$repo->add( 'Environment Details', $group );

		$expected = 'Environment Details
Active Theme: Twenty Twenty-One
Secret Key: **********
';

		$this->assertEquals( $expected, $repo->as_string() );
	}

	public function testDefaultData() {
		$repo = System_Report_Repository::instance();

		$this->assertTrue( $repo->has( 'Translations' ) );
	}
}
