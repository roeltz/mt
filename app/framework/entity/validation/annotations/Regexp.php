<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;

/** @Target("property") */
class Regexp extends AnnotableAssertion {
	
	public $pattern;
	
	function check($value, $property, $instance) {
		return new AssertionResult(preg_match($this->pattern, $value), fnn($this->message, "Value does not follow the required pattern"), array('pattern'=>$this->pattern));
	}
}
