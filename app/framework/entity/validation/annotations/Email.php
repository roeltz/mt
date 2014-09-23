<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;

/** @Target("property") */
class Email extends AnnotableAssertion {
	
	function check($value, $property, $instance) {
		return new AssertionResult(filter_var($value, FILTER_VALIDATE_EMAIL), fnn($this->message, "Not a valid e-mail address"));
	}
}
