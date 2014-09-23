<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;

/** @Target("property") */
class NotBlank extends AnnotableAssertion {
	
	function check($value, $property, $instance) {
		return new AssertionResult(strlen(trim((string) $value)) > 0, fnn($this->message, "Value is blank"));
	}
}
