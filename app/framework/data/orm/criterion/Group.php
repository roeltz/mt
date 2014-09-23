<?php

namespace data\orm\criterion;
use data\orm\ORMCriterion;

class Group implements ORMCriterion {
		
	public $property;
	
	function __construct($property) {
		$this->property = $property;
	}
	
	static function by($property) {
		return new Group($property);
	}
}
