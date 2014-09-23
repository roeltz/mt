<?php

namespace data\core;

class Collection implements Criterion {
		
	public $name;
	public $alias;
	public $joins = array();
	
	function __construct($name, $alias = null) {
		$this->name = $name;
		$this->alias = $alias;
	}
	
	function join(Collection $collection, Expression $expression) {
		$this->joins[] = $join = new Join($collection, $expression);
		return $join;
	}	
}
