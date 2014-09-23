<?php

namespace data\core\criterion;
use data\core\Criterion;

class Order implements Criterion {
	
	const ASC = "asc";
	const DESC = "desc";
	const RANDOM = "random";
	
	public $field;
	public $type;
	
	function __construct($field, $type = Order::ASC) {
		$this->field = $field;
		$this->type = $type;
	}
	
	static function asc($field) {
		return new Order($field, self::ASC);
	}

	static function desc($field) {
		return new Order($field, self::DESC);
	}

	static function random() {
		return new Order(null, self::RANDOM);
	}
}
