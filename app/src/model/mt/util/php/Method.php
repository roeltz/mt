<?php

namespace mt\util\php;

class Method extends Member {
	public $isAbstract;
	public $body = "";
	public $parameters = array();
	public $dependencies;
	
	function __construct($name, $access = Member::ACCESS_PUBLIC, $isStatic = false, $isAbstract = false) {
		parent::__construct($name, $access, $isStatic);
		$this->isAbstract = $isAbstract;
	}
	
	function addParameter(Parameter $parameter) {
		$this->parameters[] = $parameter;
		$parameter->method = $this;
		if ($parameter->typeHint && ($parameter->typeHint != "array"))
			$this->dependencies->add($parameter->typeHint);
	}
	
	function addBodyLine($line) {
		$this->body .= "$line\n";
	}
}
