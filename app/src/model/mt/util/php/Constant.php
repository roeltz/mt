<?php

namespace mt\util\php;

class Constant {
	public $name;
	public $value;
	
	function __construct($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}
}
