<?php

use PHPUnit\Framework\TestCase;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;

class ServiceContainerTest extends TestCase {

	public function testServiceIsAdded() {
		$container = new Service_Container();
		$service = array( 'foo', 'bar' );
		$service_name = 'test_service';

		$container->add( $service_name, $service );

		$this->assertSame( $container->get( $service_name ), $service );
	}

	public function testServiceIsAddedDeferred() {
		global $foo;
		$foo = 'before';
		$container = new Service_Container();

		$container->add( 'foo_service', function() {
			global $foo;
			$foo = 'after';
		}, true );

		$this->assertEquals( 'before', $foo );

		$container->get( 'foo_service' );

		$this->assertEquals( 'after', $foo );
	}

}