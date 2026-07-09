<?php

namespace Gravity_Forms\Gravity_SMTP\Routing;

class Conditional_Group {

	const AND = 'and';
	const OR = 'or';

	private $conditions = array();

	private $conjunct;

	public function __construct( $conjunct ) {
		if ( ! in_array( $conjunct, array( self::AND, self::OR ) ) ) {
			throw new \InvalidArgumentException( 'Invalid conjunct value passed to conditional group.' );
		}

		$this->conjunct = $conjunct;
	}

	public function resolve(): bool {
		if ( empty( $this->conditions ) ) {
			return false;
		}

		foreach( $this->conditions as $condition ) {
			$result = $condition->resolve();

			if ( $this->conjunct === self::AND && ! $result ) {
				return false;
			}

			if ( $this->conjunct === self::OR && $result ) {
				return true;
			}
		}

		if ( $this->conjunct === self::AND ) {
			return true;
		}

		return false;
	}

	public function add_condition( $condition ) {
		if ( ! is_a( $condition, Conditional::class ) && ! is_a( $condition, Conditional_Group::class ) ) {
			throw new \InvalidArgumentException( 'Invalid conditional passed to conditional group.' );
		}

		$this->conditions[] = $condition;
	}

}
