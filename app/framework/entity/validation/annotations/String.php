<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;

/** @Target("property") */
class String extends AnnotableAssertion {
	
	public $min;
	public $max;
	
	function check($value, $property, $instance) {
		if (!is_string($value))
			return new AssertionResult(false, fnn($this->message, "Value is not a string"));
		if (is_numeric($this->min) && strlen(trim($value)) < $this->min)
			return new AssertionResult(false, fnn($this->message, "Value is shorter than {min} character(s)"), array('min'=>$this->min));
		if (is_numeric($this->max) && strlen($value) > $this->max)
			return new AssertionResult(false, fnn($this->message, "Value is longer than {max} character(s)"), array('max'=>$this->max));
		return new AssertionResult(true);
	}
}
