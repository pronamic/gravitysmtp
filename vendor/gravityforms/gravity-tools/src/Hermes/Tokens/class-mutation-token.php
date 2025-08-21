<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens;

abstract class Mutation_Token extends Token {

	protected $type = 'Mutation';

	protected $object_type;

	protected $operation;

	protected $alias;

	public function object_type() {
		return $this->object_type;
	}

	public function operation() {
		return $this->operation;
	}

	public function alias() {
		return $this->alias;
	}

}