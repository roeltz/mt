<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;
use RuntimeException;

/** @Target("property") */
class Callback extends AnnotableAssertion {
	
	public $method;
		
	function check($value, $property, $instance) {
		if (method_exists($instance, $this->method))
			return new AssertionResult(!!call_user_func(array($instance, $this->method)), fnn($this->message, "The object did not pass the test"));
		else {
			$class = get_class($instance);
			throw new RuntimeException(__("Object of class {class} doesn't have a {method} method. Callback assertion cannot continue.", array('class'=>$class, 'method'=>$this->method)));
		}
	}
}
