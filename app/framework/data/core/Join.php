<?php

namespace data\core;

class Join {
	public $collection;
	public $expression;
	
	function __construct(Collection $collection, Expression $expression) {
		$this->collection = $collection;
		$this->expression = $expression;
	}
}
