<?php

namespace data\core\criterion;
use data\core\Criterion;

class Limit implements Criterion {
		
	public $length;
	public $offset;
	
	function __construct($length, $offset = 0) {
		$this->length = $length;
		$this->offset = $offset;
	}
	
	static function single() {
		return new Limit(1);
	}

	static function one() {
		return new Limit(1);
	}
	
	static function first($n) {
		return new Limit($n);
	}
	
	static function slice($length, $offset) {
		return new Limit($length, $offset);
	}

	static function page($n, $length) {
		return new Limit($length, ($n - 1) * $length);
	}
}
