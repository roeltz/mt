<?php

namespace mt\util\php;

class PHPClass extends Type {
	public $properties = array();
	public $implementedInterfaces = array();
	
	function implement(PHPInterface $interface) {
		$this->implementedInterfaces[] = $interface;
		$this->dependencies->add($interface);
	}
	
	function addProperty(Property $property) {
		$this->properties[$property->name] = $property;
		$property->type = $this;
	}
}
