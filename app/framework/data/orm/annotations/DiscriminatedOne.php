<?php

namespace data\orm\annotations;

/** @Target("property") */
class DiscriminatedOne extends \Annotation {
	public $class;
	public $fk;
	public $property;
	public $value;
	public $lazy = true;
	
	function hasCompoundFK() {
		return count($this->fk) > 1;
	}
}
