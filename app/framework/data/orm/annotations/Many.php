<?php

namespace data\orm\annotations;

/** @Target("property") */
class Many extends \Annotation {
	public $class;
	public $fk;
	public $lazy = true;
	public $order;
	
	function hasCompoundFK() {
		return count($this->fk) > 1;
	}	
}
