<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;

/** @Target("property") */
class Blank extends AnnotableAssertion {
	
	function check($value, $property, $instance) {
		return new AssertionResult(!strlen(trim((string) $value)), fnn($this->message, "Value is not blank"));
	}
}
