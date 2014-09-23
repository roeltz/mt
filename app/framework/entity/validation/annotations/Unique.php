<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;
use data\orm\Persistent;

/** @Target("property") */
class Unique extends AnnotableAssertion {
	
	function check($value, $property, $instance) {
		if ($instance instanceof Persistent) {
			return new AssertionResult(!$instance->count(\Restrict::eq($property, $value)), fnn($this->message, "Value '{value}' is not unique"), array('value'=>$value));
		} 
	}
}
