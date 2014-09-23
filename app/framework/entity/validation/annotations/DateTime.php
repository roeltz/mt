<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;

/** @Target("property") */
class DateTime extends AnnotableAssertion {
	
	public $max;
	public $min;
	
	function check($value, $property, $instance) {
			
		if (!($value instanceof \DateTime))
			return new AssertionResult(false, fnn($this->message, "Value is not a date"));

		$max = is_null($this->max) ? null : new \DateTime($this->max);
		if ($max && $value->getTimestamp() > $max->getTimestamp())
			return new AssertionResult(false, fnn($this->message, "Date is after {max}"), array('max'=>$max->format("Y-m-d H:i:s")));
		
		$min = is_null($this->min) ? null : new \DateTime($this->min);
		if ($min && $value->getTimestamp() < $min->getTimestamp())
			return new AssertionResult(false, fnn($this->message, "Date is before {min}"), array('min'=>$min->format("Y-m-d H:i:s")));

		return new AssertionResult(true);
	}
}
