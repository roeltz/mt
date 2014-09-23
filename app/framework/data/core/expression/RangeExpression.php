<?php

namespace data\core\expression;
use data\core\Expression;

class RangeExpression implements Expression {
	
	public $field;
	public $a;
	public $b;
	
	function __construct($field, $a, $b) {
		$this->field = $field;
		$this->a = $a;
		$this->b = $b;
	}
}
