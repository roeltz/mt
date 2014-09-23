<?php

namespace data\orm\transformation;
use data\orm\Transformation;

class ZipTransformation implements Transformation {
	
	public $compressionLevel;
	
	function __construct($compressionLevel = 9) {
		$this->compressionLevel = $compressionLevel;
	}
	
	function apply($value, $params, $isUpdate = false) {
		return gzcompress($object, $this->compressionLevel);
	}
	
	function undo($value, $params) {
		return gzuncompress($value, $this->compressionLevel);
	}
}
