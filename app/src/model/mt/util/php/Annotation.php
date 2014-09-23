<?php

namespace mt\util\php;

class Annotation {
	public $class;
	public $parameters = array();
	
	function __construct($class, $value = null) {
		$this->class = $class;
		if (func_num_args() == 2)
			$this->addParameter("value", $value);
	}
	
	function addParameter($name, $value = true) {
		$this->parameters[$name] = $value;
	}

	function removeParameter($name) {
		unset($this->parameters[$name]);
	}
}
