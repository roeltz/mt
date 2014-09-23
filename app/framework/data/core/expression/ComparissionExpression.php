<?php

namespace data\core\expression;
use data\core\Expression;

class ComparissionExpression implements Expression {
	
	public $field;
	public $operator;
	public $value;
	public $valueIsField;
	
	function __construct($field, $operator, $value, $valueIsField = false) {
		$this->field = $field;
		$this->operator = $operator;
		$this->value = $value;
		$this->valueIsField = $valueIsField;
	}
}
