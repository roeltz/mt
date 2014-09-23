<?php

namespace entity\core;
use entity\validation\Descriptor as ValidationDescriptor;
use ReflectionAnnotatedClass;

class Descriptor {
	
	public $class;
	public $properties;
	
	public $stringRepresentation;
	public $validation;
	
	function __construct($class) {
		$this->validation = ValidationDescriptor::fromAnnotatedClass($class);
		$annotatedClass = new ReflectionAnnotatedClass($this->class = $class);

		if ($toString = $annotatedClass->getAnnotation("entity\\core\\annotations\\ToString"))
			$this->stringRepresentation = $toString->value;
		
		foreach($annotatedClass->getProperties() as $property) {
			if ($property->isPublic()) {
				$this->properties[] = $name = $property->getName();
			}
		}
	}
}

?>