<?php

namespace data\orm\transformation;
use data\orm\Transformation;

class PhpSerializationTransformation implements Transformation {
	
	function apply($value, $params, $isUpdate = false) {
		return serialize($value);
	}
	
	function undo($value, $params) {
		return unserialize($value);
	}
}
