<?php

namespace data\orm\transformation;
use data\orm\Transformation;

class JsonTransformation implements Transformation {
	
	function apply($value, $params, $isUpdate = false) {
		return json_encode($value);
	}
	
	function undo($value, $params) {
		return json_decode($value);
	}
}
