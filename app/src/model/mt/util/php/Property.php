<?php

namespace mt\util\php;

class Property extends Member {
	public $defaultValue;
	public $hasDefaultValue;
	
	function __construct($name, $access = Member::ACCESS_PUBLIC, $isStatic = false, $defaultValue = null) {
		parent::__construct($name, $access, $isStatic);
		$this->defaultValue = $defaultValue;
		$this->hasDefaultValue = (func_num_args() == 4) && ($defaultValue != null);
	}
}
