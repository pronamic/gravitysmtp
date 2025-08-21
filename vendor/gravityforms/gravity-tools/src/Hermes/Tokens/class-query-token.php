<?php

namespace Gravity_Forms\Gravity_Tools\Hermes\Tokens;

class Query_Token extends Token {

	protected $type = 'Query';

	protected $arguments;

	protected $object_type;

	protected $items;

	protected $alias;

	public function object_type() {
		return $this->object_type;
	}

	public function items() {
		return $this->items;
	}

	public function alias() {
		return $this->alias;
	}

	public function parse( $contents, $args = array() ) {
		preg_match_all( $this->get_parsing_regex(), $contents, $results );

		$values = $this->tokenize( $results );

		$this->object_type = 'query';
		$this->items      = $values->fields();
	}

	private function tokenize( $parts ) {
		$matches   = $parts[0];
		$marks     = $parts['MARK'];
		$data      = array();

		$data = new Data_Object_From_Array_Token( $matches, $marks, array( 'object_type' => $this->type, 'alias' => false ) );
		$data->set_parent( $this );

		return $data;
	}

	protected function regex_types() {
		//(?| (*MARK:arg_group)\([^\)]+\) | (*MARK:identifier)[_A-Za-z][_0-9A-Za-z]* | (*MARK:open_bracket){ | (*MARK:close_bracket)} )
		return array(
			'arg_group'     => '\([^\)]+\)',
			'alias'         => '[_A-Za-z][_0-9A-Za-z]*:',
			'identifier'    => '[_A-Za-z][_0-9A-Za-z]*',
			'open_bracket'  => '{',
			'close_bracket' => '}',
		);
	}

	/**
	 * @return Data_Object_From_Array_Token[]
	 */
	public function children() {
		return $this->items();
	}

}
