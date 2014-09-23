<?php

namespace data\orm\annotations;
use data\orm\cache\TransformationsCache;

/** @Target("property") */
class Transform extends \Annotation {
	public $filter;
	public $params;
	
	protected function checkConstraints($target) {
		$this->filter = fnn($this->filter, $this->value);
		unset($this->value);
	}
	
	function apply($value, $isUpdate = false) {
		$t = TransformationsCache::get($this->filter);
		return $t->apply($value, $this->params, $isUpdate);
	}
	
	function undo($value) {
		$t = TransformationsCache::get($this->filter);
		return $t->undo($value, $this->params);
	}
}
