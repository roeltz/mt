<?php

namespace mt\util\php;

class Member {
	const ACCESS_PUBLIC = "public";
	const ACCESS_PRIVATE = "private";
	const ACCESS_PROTECTED = "protected";
	
	public $type;
	public $name;
	public $access;
	public $docBlock;
	public $isStatic;
	
	function __construct($name, $access = Member::ACCESS_PUBLIC, $isStatic = false) {
		$this->name = $name;
		$this->access = $access;
		$this->isStatic = $isStatic;
		$this->docBlock = new DocBlock();
	}
}
