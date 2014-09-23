<?php

namespace mt\util\php;

class Parameter {
	public $method;
	public $name;
	public $defaultValue;
	public $hasDefaultValue = false;
	public $typeHint;
	
	function __construct($name, $typeHint = null, $defaultValue = null) {
		$this->name = $name;
		$this->typeHint = $typeHint;
		$this->defaultValue = $defaultValue;
		$this->hasDefaultValue = func_num_args() == 3;
	}
}
