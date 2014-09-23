<?php

namespace data\core\expression;
use data\core\Expression;

class ListExpression implements Expression {
		
	const IN = "in";
	const NOT_IN = "not-in"; 
	
	public $field;
	public $operator;
	public $values;
	
	function __construct($field, $operator, array $values) {
		$this->field = $field;
		$this->operator = $operator;
		$this->values = $values;
	}
}
