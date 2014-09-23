<?php

namespace entity\validation;
use ReflectionAnnotatedClass;

abstract class Validator {
	
	static function validate($object) {
		$descriptor = $object instanceof Validable ? $object->getValidationDescriptor() : Descriptor::fromAnnotatedClass(get_class($object));
		$errors = array();
		foreach(array_keys($descriptor->assertions) as $property)
			if (count($result = self::validateProperty($object, $property, $descriptor)))
				$errors[$property] = $result;
		return $errors;
	}
	
	static function validateProperty($object, $property, Descriptor $descriptor = null) {
		$descriptor = $descriptor ? $descriptor : ($object instanceof Validable ? $object->getValidationDescriptor() : Descriptor::fromAnnotatedClass(get_class($object)));
		$errors = array();
		
		$class = new ReflectionAnnotatedClass(get_class($object));
		
		if (!$descriptor->isPropertyNullable($property) || !is_null($object->$property)) {
			foreach($descriptor->assertions[$property] as $assertion) {
				$result = $assertion->check($property == "#class" ? null : $object->$property, $property, $object);
				if (!$result->success) {
					if ($result->message) {
						$errors[] = fill($result->message, (array) $result->values);
					} else {
						$class = get_class($object);
						$assertionClass = get_class($assertion);
						$errors[] = ($property == "#class") ? "$class object failed $assertionClass" : "$class::$property failed $assertionClass";
					}
				}
			}
		}
		return $errors;
	}
	
	static function isValid($object) {
		return !count(self::validate($object));
	}	
}
