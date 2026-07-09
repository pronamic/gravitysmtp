<?php

namespace Gravity_Forms\Gravity_SMTP\Routing;

use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;

class Static_Conditional extends Conditional {

	/**
	 * @var mixed
	 */
	private $value;

	public function __construct( $value ) {
		$this->value = $value;
	}

	public function resolve(): bool {
		return Booliesh::get( $this->value );
	}
}
