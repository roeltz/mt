<?php

namespace data\core\expression;
use data\core\Expression;

class JunctionExpression implements Expression {
	
	const CONJUNCTION = "and";
	const DISJUNCTION = "or";
	
	public $operator;
	public $expressions;
	
	function __construct($operator, array $expressions) {
		$this->operator = $operator;
		foreach($expressions as $expression)
			if ($expression instanceof Expression)
				$this->expressions[] = $expression;
			else
				throw new InvalidArgumentException(__("Attempted to include a non-expression in junction expression"));
	}
}
