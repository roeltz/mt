<?php

namespace data\core\criterion;
use data\core\Criterion;

class Distinct implements Criterion {
		
	public $field;
	
	function __construct($field) {
		$this->field = $field;
	}
}
