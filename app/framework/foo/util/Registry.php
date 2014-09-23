<?php

namespace foo\util;
use Exception;

abstract class Registry {
	
	const CONSTRUCTOR = 1;
	const SINGLETON = 2;
	const COLLECTION = 4;
	const LOCKED = 8;
	
	private static $registry = array();
	private static $singletons = array();

	static function setByClass($class, $category, $key, $value, $flags = 0) {
		$currentValue = @self::$registry[$class][$category][$key]['value'];
		$currentFlags = @self::$registry[$class][$category][$key]['flags'];
		
		if (is_null($currentFlags)) {
			if ($flags & self::COLLECTION)
				$value = array($value);
		} else {
			if ($currentFlags & self::LOCKED)
				throw new Exception(__('Element for this key is locked. Cannot be changed.'));
			if ($currentFlags & self::COLLECTION)
				$value = array_merge(to_array($currentValue), array($value));
		}
		
		if ($flags & self::CONSTRUCTOR || $flags & self::SINGLETON) {
			if (!is_callable($value))
				throw new Exception(__('When registering a singleton or a constuctor, a function must be supplied as value'));
			$register = array('constructor'=>$value, 'flags'=>$flags);
		} else
			$register = array('value'=>$value, 'flags'=>$flags);
		
		self::$registry[$class][$category][$key] = $register;
	}

	static function set($key, $value) {
		self::setByClass(get_called_class(), "default", $key, $value);
	}

	static function getByClass($class, $category, $key = null, $withKey = false) {
		$compilation = array();
		foreach(self::$registry as $registryClass=>$registry)
			if ($class == $registryClass || is_subclass_of($class, $registryClass))
				if (isset($registry[$category]))
					foreach($registry[$category] as $registryKey=>$register)
						if (!$key || $registryKey == $key)
							$compilation[$registryKey] = array_merge((array) @$compilation[$registryKey], to_array(self::resolve($registryClass, $category, $registryKey, $register)));
		if (is_null($key) || $withKey)
			return $compilation;
		else
			return $compilation[$key];
	}
	
	static function get($key) {
		$value = self::getByClass($class = get_called_class(), "default", $key);
		if (!(@self::$registry[$class]["default"][$key]["flags"] & self::COLLECTION))
			$value = reset($value);
		return $value;
	}
	
	static function addByClass($class, $category, $key, $value) {
		self::setByClass($class, $category, $key, $value, self::COLLECTION);
	}
	
	static function add($key, $value) {
		self::addByClass(get_called_class(), "default", $key, $value);
	}
	
	static function setSingletonByClass($class, $category, $key, $constructor) {
		self::setByClass($class, $category, $key, $constructor, self::SINGLETON);
	}

	static function setSingleton($key, $constructor) {
		self::setSingletonByClass(get_called_class(), "default", $key, $constructor);
	}
	
	static function setConstructorByClass($class, $category, $key, $constructor) {
		self::setByClass($class, $category, $key, $constructor, self::CONSTRUCTOR);
	}

	static function setConstructor($key, $constructor) {
		self::setConstructorByClass(get_called_class(), "default", $key, $constructor);
	}
	
	static function lockByClass($class, $category, $key) {
		self::$registry[$class][$category][$key]['flags'] |= self::LOCKED; 
	}
	
	static function lock($key) {
		self::lockByClass(get_called_class(), "default", $key);
	}

	protected static function resolve($class, $category, $key, array $register) {
		if ($register['flags'] & self::CONSTRUCTOR) {
			return call_user_func($register['constructor']);
		} elseif ($register['flags'] & self::SINGLETON) {
			if (!isset(self::$singletons[$class][$category][$key]))
				self::$singletons[$class][$category][$key] = call_user_func($register['constructor']);
			return self::$singletons[$class][$category][$key];
		} else {
			return $register['value'];
		}
	}
}