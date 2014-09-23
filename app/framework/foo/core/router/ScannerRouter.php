<?php

namespace foo\core\router;
use Annotation;
use ReflectionAnnotatedClass;
use ReflectionAnnotatedMethod;
use foo\core\Importer;
use foo\util\cache\Cache;

class ScannerRouter extends PatternRouter {
	
	function __construct($_ = null) {
		if (Cache::get("file")->has("scanned-mappings")) {
			$this->rules = Cache::get("file")->get("scanned-mappings");
		} else {
			foreach(func_get_args() as $arg)
				if (is_string($arg))
					$this->scanPath($arg);
				elseif (is_array($arg))
					$this->addRules($arg);

			foreach(Importer::getPaths() as $path)
				$this->scanPath($path);
			foreach(Importer::getClasses() as $file)
				$this->scanFile($file);
			
			Cache::get("file")->set("scanned-mappings", $this->rules);
		}
	}
	
	protected function scanPath($path) {
		$path = preg_replace('/(\\\\|\\/)$/', "", $path);
		foreach(rglob("$path/*.php") as $file) {
			if ($rules = $this->scanFile($file))
				$this->rules = array_merge($this->rules, $rules);
		}
	}
	
	protected function scanFile($file) {
		if (!preg_match('/class\s+/', file_get_contents($file))) return;
		$before = get_declared_classes();
		include_once $file;
		$after = get_declared_classes();
		$diff = array_diff($after, $before);
		$rules = array();
		foreach($diff as $class) {
			$class = new ReflectionAnnotatedClass($class);
			foreach($class->getMethods() as $method)
				if (($mapping = $method->getAnnotation("foo\\core\\annotations\\req\\Mapping")) && $mapping->pattern)
					$rules[] = $this->buildRule($mapping, $method);
		}
		return $rules;
	}
	
	protected function buildRule(Annotation $mapping, ReflectionAnnotatedMethod $method) {
		$c = $method->getDeclaringClass()->getName();
		$m = $method->getName();
		if ($mapping->pattern == "/") $mapping->pattern = "";
		return new PatternRule("$c:$m", $mapping->pattern, $mapping->source, $mapping->profile);
	}
}
