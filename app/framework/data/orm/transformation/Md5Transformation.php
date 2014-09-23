<?php

namespace data\orm\transformation;
use data\orm\Transformation;

class Md5Transformation implements Transformation {
	
	function apply($value, $params, $isUpdate = false) {
		if (strlen($value) != 32)
			return sha1($value);
		else
			return $value;
	}
	
	function undo($value, $params) {
		return $value;
	}
}
