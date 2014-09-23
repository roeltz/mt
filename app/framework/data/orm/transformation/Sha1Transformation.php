<?php

namespace data\orm\transformation;
use data\orm\Transformation;

class Sha1Transformation implements Transformation {
	
	function apply($value, $params, $isUpdate = false) {
		if (strlen($value) != 40)
			return sha1($value);
		else
			return $value;
	}
	
	function undo($value, $params) {
		return $value;
	}
}
