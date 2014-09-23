<?php

namespace mt\util\php;

class PHPInterface extends Type {
	
	function addMethod(Method $method) {
		parent::addMethod($method);
		$method->isAbstract = true;
	}
}
