<?php

namespace entity\validation;
use Annotation;

interface Assertion {
	function check($value, $property, $instance);
}
