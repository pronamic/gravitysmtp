<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens;

class Field_Token extends Token_From_Array {

	protected $type = 'Field';

	protected $name;

	protected $alias;

	protected $arguments;

	public function name() {
		return $this->name;
	}

	public function alias() {
		return $this->alias;
	}

	public function object_type() {
		return $this->name;
	}

	public function arguments() {
		return $this->arguments;
	}

	public function parse( &$matches, &$marks, $additional_args = array() ) {
		$this->name  = $additional_args['name'];
		$this->alias = $additional_args['alias'];

		if ( isset( $additional_args['arguments'] ) ) {
			$this->arguments = $additional_args['arguments'];
		}
	}

	public function children() {
		return $this->arguments();
	}

}