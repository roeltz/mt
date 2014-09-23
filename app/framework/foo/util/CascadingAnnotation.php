<?php

namespace foo\util;
use Annotation;
use ReflectionAnnotatedMethod;

class CascadingAnnotation extends Annotation {
	
	static function get($class, $method, $annotation) {
		$methodObject = new ReflectionAnnotatedMethod($class, $method);
		if ($methodAnnotation = $methodObject->getAnnotation($annotation))
			return $methodAnnotation;
		else
			return $methodObject->getDeclaringClass()->getAnnotation($annotation);
	}
}
