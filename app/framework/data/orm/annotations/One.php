<?php

namespace data\orm\annotations;

/** @Target("property") */
class One extends \Annotation {
	public $class;
	public $fk;
	public $lazy = true;
	
	function hasCompoundFK() {
		return count($this->fk) > 1;
	}
}
