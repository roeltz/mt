<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;

/** @Target("property") */
class Number extends AnnotableAssertion {
	
	public $min;
	public $max;
	public $gt;
	public $lt;
	public $positive;
	public $natural;
	public $integer;
	
	function check($value, $property, $instance) {
		if (!is_numeric($value))
			return new AssertionResult(false, fnn($this->message, "Value is not a number"));
		if (is_numeric($this->min) && $value < $this->min)
			return new AssertionResult(false, fnn($this->message, "Number is less than {min}"), array('min'=>$this->min));
		if (is_numeric($this->gt) && $value <= $this->gt)
			return new AssertionResult(false, fnn($this->message, "Number is less than {gt}"), array('gt'=>$this->gt));
		if (is_numeric($this->max) && $value > $this->max)
			return new AssertionResult(false, fnn($this->message, "Number is greater than {max}"), array('max'=>$this->max));
		if (is_numeric($this->lt) && $value >= $this->lt)
			return new AssertionResult(false, fnn($this->message, "Number is greater than {lt}"), array('lt'=>$this->lt));
		if ($this->positive && $value < 0)
			return new AssertionResult(false, fnn($this->message, "Number is not positive"));
		if ($this->integer && round($value) != $value)
			return new AssertionResult(false, fnn($this->message, "Number is not integer"));
		if ($this->natural && ($value < 1 || round($value) != $value))
			return new AssertionResult(false, fnn($this->message, "Number is not natural"));;
		return new AssertionResult(true);
	}
}
