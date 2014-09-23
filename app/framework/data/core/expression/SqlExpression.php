<?php

namespace data\core\expression;
use data\core\Expression;

class SqlExpression implements Expression {
	
	public $string;
	public $parameters;
	
	function __construct($string, array $parameters = null) {
		$this->string = $string;
		$this->parameters = $parameters;
	}
}
