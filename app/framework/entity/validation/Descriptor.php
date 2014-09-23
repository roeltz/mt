<?php

namespace entity\validation;
use ReflectionAnnotatedClass;

class Descriptor {
	
	public $assertions = array();
	public $nullables = array();
	
	function add($property, Assertion $assertion) {
		$this->assertions[$property][] = $assertion;
		return $this;
	}
	
	function addNullableProperty($property) {
		$this->nullables[] = $property;
	}
	
	function isPropertyNullable($property) {
		return in_array($property, $this->nullables);
	}
	
	static function fromAnnotatedClass($class) {
		$config = new Descriptor();
		$annotatedClass = new ReflectionAnnotatedClass($class);
		
		foreach($annotatedClass->getAllAnnotations() as $annotation) {
			if ($annotation instanceof Assertion)
				$config->add("#class", $annotation);			
		}
		
		foreach($annotatedClass->getProperties() as $property) {
			$propertyName = $property->getName();
			
			foreach($property->getAllAnnotations() as $annotation) {
				if ($annotation instanceof Assertion)
					$config->add($propertyName, $annotation);					
			}
			
			if (!$property->hasAnnotation("data\\orm\\annotations\\NotNull"))
				$config->addNullableProperty($propertyName);
		}
		
		return $config;
	}
}
