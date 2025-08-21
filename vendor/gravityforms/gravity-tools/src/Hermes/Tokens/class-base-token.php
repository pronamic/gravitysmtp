<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens;

abstract class Base_Token {

	protected $type;

	protected $parent;

	public function type() {
		return $this->type;
	}

	public function parent() {
		return $this->parent;
	}

	public function set_parent( Base_Token $parent ) {
		$this->parent = $parent;
	}

	abstract public function children();

}