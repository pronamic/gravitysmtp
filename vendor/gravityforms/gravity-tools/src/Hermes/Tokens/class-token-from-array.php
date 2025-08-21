<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens;

abstract class Token_From_Array extends Base_Token {

	public function __construct( &$matches, &$marks, $additional_args = array() ) {
		$this->parse( $matches, $marks, $additional_args );
	}

	abstract public function parse( &$matches, &$marks, $additional_args = array() );
}