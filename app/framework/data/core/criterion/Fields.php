<?php

namespace data\core\criterion;
use data\core\Criterion;

class Fields implements Criterion {
		
	public $fields;
	
	function __construct(array $fields) {
		$this->fields = $fields;
	}
}
