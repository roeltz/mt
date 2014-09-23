<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;

/** @Target("property") */
class Select extends AnnotableAssertion {
	
	public $values;
	public $source;
	public $min = 1;
	public $max = 1;
	public $unique = true;
	
	function check($value, $property, $instance) {
		$values = $this->source ? call_user_func(array($instance, $this->source)) : $this->values;
		if ($this->unique)
			$values = array_unique($values);
		$selected = array_intersect($values, (array) $value);
		if (count($selected) > $this->max)
			return new AssertionResult(false, fnn($this->message, ($this->max == $this->min && $this->max == 1) ? "Must pick a valid option" : "Must select at most {max} value(s)"), array('max'=>$this->max));
		if (count($selected) < $this->min)
			return new AssertionResult(false, fnn($this->message, ($this->max == $this->min && $this->max == 1) ? "Must pick a valid option" : "Must select at least {min} value(s)"), array('min'=>$this->min));
		return new AssertionResult(true);
	}
}
