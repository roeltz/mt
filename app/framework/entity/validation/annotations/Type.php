<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;
use RuntimeException;

/** @Target("property") */
class Type extends AnnotableAssertion {
	
	public $name;
	public $class;
		
	function check($value, $property, $instance) {
		if ($class = $this->class) {
			return new AssertionResult($value instanceof $class, fnn($this->message, __("Object is not of the required {class} class")), array('class'=>$this->class));
		} else {
			if (function_exists(($fn = "is_{$this->name}")))
				return new AssertionResult($fn($value), fnn($this->message, __("Value is not of the required {name} type")), array('name'=>$this->name));
			else
				throw new RuntimeException(__("Invalid '{name}' type for assert\Type", array('name'=>$this->name)));
		}
	}
}
