<?php

namespace foo\core;
use ReflectionAnnotatedMethod;
use foo\core\annotations\req\Option;

class Result {
	
	public $data;
	public $options;
	
	function __construct($data = null, $options = null) {
		$this->data = $data;
		$this->options = fnn($options, array());	
	}
	
	static function from($result, ReflectionAnnotatedMethod $method = null) {
		$result = is_a($result, 'foo\\core\\Result') ? $result : new Result($result);
		$options = array();
		
		if ($method)
			$options = self::getAnnotatedOptions($method);
		
		foreach($result->options as $name=>$value)
			$options[$name] = $value;
		
		$result->options = $options;
		
		return $result;
	}
	
	static function getAnnotatedOptions(ReflectionAnnotatedMethod $method) {
		$options = array();
		
		foreach($method->getDeclaringClass()->getAllAnnotations() as $annotation)
			if ($annotation instanceof Option)
				$options[$annotation->name] = $annotation->value;

		foreach($method->getAllAnnotations() as $annotation)
			if ($annotation instanceof Option)
				$options[$annotation->name] = $annotation->value;
			
		return $options;		
	}
}
