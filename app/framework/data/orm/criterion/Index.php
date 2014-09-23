<?php

namespace data\orm\criterion;
use data\orm\ORMCriterion;

class Index implements ORMCriterion {
		
	public $byPK;
	public $property;
	
	function __construct($byPK = true, array $property = null) {
		if (!($this->byPK = $byPK))
			$this->property = $property;
	}
	
	static function byPK() {
		return new Index();
	}
	
	static function by($property) {
		return new Index(false, $property);
	}
}
