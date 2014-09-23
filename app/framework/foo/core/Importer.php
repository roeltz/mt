<?php

namespace foo\core;
use foo\core\locale\Locale;
use foo\util\ConstantSettings;

abstract class Importer {
	
	private static $init = false;
	private static $cache;
	private static $paths = array();
	private static $hookPaths = array();
	private static $callbacks = array();
	private static $classes = array();
	private static $namespaces = array();
	private static $namespaceAliases = array();
	
	static function getPaths() {
		return self::$paths;
	}

	static function getHookPaths() {
		return self::$hookPaths;
	}

	static function getClasses() {
		return self::$classes;
	}
	
	static function getNamespaces() {
		return self::$namespaces;
	}

	static function getNamespaceAliases() {
		return self::$namespaceAliases;
	}	
	
	static function init() {
		spl_autoload_register('foo\\core\\Importer::search');
		self::$cache = unserialize(@file_get_contents(CFG_CACHE_PATH . "/importer"));
	}
	
	static function load($class, $file, $cached = false) {
		if (file_exists($file)) {
			require_once $file;
	
			if (class_exists($class) || interface_exists($class)) {
				self::triggerCallbacks("class", $class);
				if (!$cached)
					self::cache($class, $file);
				return true;
			}
		}
	}
	
	static function search($class) {
		
		if (isset(self::$cache[$class]))
			if (self::load($class, self::$cache[$class], true))
				return true;

		if (isset(self::$classes[$class]))
			if (self::load($class, self::$classes[$class], true))
				return true;
			
		if (strstr($class, "\\")) {
			
			foreach(self::$namespaceAliases as $ns=>$nss) {
				foreach($nss as $realNS) {
					if (strpos($class, "$ns\\") === 0) {
						$original = preg_replace('/^' . preg_quote("$ns", '/') . '/', $realNS, $class);
						if (self::search($original)) {
							class_alias($original, $class);
							return true;
						}
					}
				}
			}
			
			foreach(self::$namespaces as $ns=>$paths) {
				if (strpos($class, "$ns\\") === 0) {
					$partialClass = preg_replace('/^' . preg_quote("$ns", '/') . '/', '', $class);
					foreach($paths as $path) {
						if (self::load($class, "$path$partialClass.php"))
							return true;
					}
				}
			}
		}
		
		$resource = str_replace('\\', '/', $class);
		foreach(self::$paths as $path) {
			if (file_exists($file = "$path/$resource.php")) {
				if (self::load($class, $file))
					return true;
			}
		}		
	}
	
	static function hook($hook) {
		foreach(self::$hookPaths as $path) {
			if (file_exists($file = "$path$hook.php")) {
				require_once $file;
			}
		}
	}
	
	static function addPath($_) {
		foreach(func_get_args() as $path)
			self::$paths = array_merge(self::$paths, (array) self::expand($path));
	}

	static function addHookPath($_) {
		foreach(func_get_args() as $path)
			self::$hookPaths = array_merge(self::$hookPaths, (array) self::expand($path));
	}

	static function addClass($class, $path) {
		self::$classes[$class] = $path;
	}

	static function addNamespace($ns, $path) {
		self::$namespaces[$ns][] = $path;
	}

	static function addNamespaceAlias($ns, $realNS) {
		self::$namespaceAliases[$ns][] = $realNS;
	}
	
	static function addPackage($path) {
		$originalPath = $path;
		$ini = parse_ini_file($path . DIRECTORY_SEPARATOR . ".package", true);
		$path = realpath($path) . DIRECTORY_SEPARATOR;
		
		self::triggerCallbacks("package", $originalPath, $ini);

		if (isset($ini['config']))
			foreach($ini['config'] as $p)
				ConstantSettings::load("$path$p");

		if (isset($ini['classes']))
			foreach($ini['classes'] as $class=>$p)
				self::addClass($class, "$path$p");

		if (isset($ini['class-aliases']))
			foreach($ini['class-aliases'] as $alias=>$class)
				class_alias($class, $alias);

		if (isset($ini['namespaces']))
			foreach($ini['namespaces'] as $ns=>$nsp)
				foreach((array) $nsp as $p)
					self::addNamespace($ns, "$path$p");

		if (isset($ini['namespace-aliases']))
			foreach($ini['namespace-aliases'] as $ns=>$realNS)
				self::addNamespaceAlias($ns, $realNS);
							
		if (isset($ini['paths']))
			foreach($ini['paths'] as $p)
				self::addPath("$path$p");
		
		if (isset($ini['hook-paths']))
			foreach($ini['hook-paths'] as $p)
				self::addHookPath("$path$p");

		if (isset($ini['require']))
			foreach($ini['require'] as $p)
				require_once "$path$p";

		if (isset($ini['i18n']))
			foreach($ini['i18n'] as $domain=>$p)
				Locale::registerResourceFilename("$path$p", $domain);
	}
	
	static function addCallback($type, $id, $callback) {
		self::$callbacks[$type][$id][] = $callback;
	}
	
	static function triggerCallbacks($type, $id, $data = null) {
		if ($id != "*")
			self::triggerCallbacks($type, "*");
		if (isset(self::$callbacks[$type][$id]))
			foreach(self::$callbacks[$type][$id] as $callback)
				$callback($data);
	}
	
	static function expand($path) {
		if (preg_match('/\/\*$/', $path)) {
			$paths = array(realpath(chop($path, "/*")) . DIRECTORY_SEPARATOR);
			foreach(glob($path, GLOB_ONLYDIR) as $subpath) {
				$paths[] = self::expand("$subpath/*");
			}
			return array_flatten($paths);
		} else {
			return $path . DIRECTORY_SEPARATOR;
		}
	}

	static function cache($class, $path) {
		self::$cache[$class] = $path;
		file_put_contents(CFG_CACHE_PATH . "/importer", serialize(self::$cache));
	}
	
	static function getCachedPath($class) {
		return @self::$cache[$class];
	}
}

Importer::init();
