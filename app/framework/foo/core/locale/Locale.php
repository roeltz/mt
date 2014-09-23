<?php

namespace foo\core\locale;
use Exception;

class Locale {
	private static $accepted;
	private static $current;
	private static $resources = array();
	private static $resourceClasses = array();
	public $code;
	
	static function accepted($_ = null) {
		if (func_num_args())
			self::$accepted = array_flatten(func_get_args());
		else
			return self::$accepted;
	}
	
	static function set(Locale $locale) {
		self::$current = $locale;
	}
	
	static function get() {
		return self::$current;
	}
	
	static function registerResourceClass($class, $validator) {
		self::$resourceClasses[$class] = $validator;
	}
	
	static function registerResource(Resource $resource, $domain = "default") {
		self::$resources[$domain][] = $resource;
	}
	
	static function registerResourceFilename($filename, $domain = "default") {
		foreach(self::$resourceClasses as $class=>$validator) {
			if ($validator($filename))
				return self::registerResource(new $class($filename), $domain);
		}
		throw new Exception(__("Could not find suitable resource class for '{filename}'", array('filename'=>$filename)));
	}
	
	function __construct($code) {
		$this->code = $code;
	}
	
	function setEnvironment() {
		setlocale(LC_ALL, $this->code);
		self::set($this);
	}

	function translate($message, $domain = "default") {
		if (isset(self::$resources[$domain]))
			foreach(self::$resources[$domain] as $resource)
				if ($translation = $resource->getMessage($message, $this->code))
					return $translation;
		return $message;
	}
}
