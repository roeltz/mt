<?php

namespace data\core\expression;
use data\core\Expression;

class NegationExpression implements Expression {
	
	public $expression;
	
	function __construct(Expression $expression) {
		$this->expression = $expression;
	}
}
