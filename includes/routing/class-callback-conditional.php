<?php

namespace Gravity_Forms\Gravity_SMTP\Routing;

use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use InvalidArgumentException;

class Callback_Conditional extends Conditional {

	/**
	 * @var array
	 */
	private $args;

	/**
	 * @var callable
	 */
	private $callback;

	public function __construct( $callback, $args = array() ) {
		if ( ! is_callable( $callback ) ) {
			throw new InvalidArgumentException( 'Callback conditional must be provided a valid callable callback as argument 1.' );
		}

		$this->args     = $args;
		$this->callback = $callback;
	}

	public function resolve(): bool {
		$result = call_user_func_array( $this->callback, $this->args );

		return Booliesh::get( $result );
	}
}
