<?php

namespace data\core;

class Aggregate {
	
	const MAX = "max";
	const MIN = "min";
	const AVG = "avg";
	const SUM = "sum";
	
	public $field;
	public $operation;
	
	function __construct($field, $operation) {
		$this->field = $field;
		$this->operation = $operation;
	}
	
	static function max($field) {
		return new Aggregate($field, self::MAX);
	}

	static function min($field) {
		return new Aggregate($field, self::MIN);
	}
	
	static function avg($field) {
		return new Aggregate($field, self::AVG);
	}

	static function sum($field) {
		return new Aggregate($field, self::SUM);
	}
}
