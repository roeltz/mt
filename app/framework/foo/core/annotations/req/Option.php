<?php

namespace foo\core\annotations\req;
use foo\core\annotations\FooAnnotation;

class Option extends FooAnnotation {
	public $name;
	public $value = true;
	
	function __toString() {
		return (string) $this->value;
	}

	static function create($value) {
		$class = get_called_class();
		return new $class(array('value'=>$value));
	} 
	
}
