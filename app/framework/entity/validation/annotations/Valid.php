<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;
use entity\validation\Validator;
use entity\validation\Validable;
use RuntimeException;

/** @Target("property") */
class Valid extends AnnotableAssertion {
	
	function check($value, $property, $instance) {
		if ($value instanceof Validable)
			return $value->isValid();
		elseif (is_object($value)) {
			return Validator::isValid($value);
		} elseif (!is_null($value))
			throw new RuntimeException(__("This object is not validable"));
		else
			return new AssertionResult(false, fnn($this->message, __("This object is not valid"))); 
	}
}
