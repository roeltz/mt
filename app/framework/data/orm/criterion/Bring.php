<?php

namespace data\orm\criterion;
use data\orm\ORMCriterion;

class Bring implements ORMCriterion {
		
	public $properties;
	
	function __construct(array $properties) {
		$this->properties = $properties;
	}
	
	static function fields($_) {
		return new Bring(array_filter(array_flatten(func_get_args())));
	}
}
