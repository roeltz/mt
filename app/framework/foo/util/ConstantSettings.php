<?php

namespace foo\util;
use foo\util\cache\Cache;

abstract class ConstantSettings {
	
	private static $cache = array();
		
	static function load($path, $prefix = "CFG") {
		$id = strtolower(preg_replace('/\\\\|\\/|:/', '-', $path));
		
		if (Cache::get("file")->has("settings", $id)) {
			self::$cache = Cache::get("file")->get("settings", $id);
		} else {
			$before = self::$cache;
			self::extract($path, $prefix);
			$diff = array_diff(self::$cache, $before);
			Cache::get("file")->set("settings", $id, $diff);
		}
		
		foreach(self::$cache as $key=>$value)
			if (!defined($key))
				define($key, $value);
	}
	
	private static function extract($path, $prefix) {
		$config = parse_ini_file($path, true);
		foreach($config as $section=>$properties) {
			$section = str_replace('.', '_', $section);
			$section = str_replace('-', '_', $section);
			foreach($properties as $key=>$value) {
				if ($key == "include") {
					foreach((array) $value as $item)
						self::extract(self::resolveIncludePath($path, $item, $section), $prefix);
				} else {
					$key = str_replace('-', '_', $key);
					$key = strtoupper("{$prefix}_{$section}_{$key}");
					self::$cache[$key] = $value;
				}
			}
		}
	}
	
	private static function resolveIncludePath($origin, $include, $section) {
		if (file_exists($path = realpath(dirname($origin) . "/" . $include)))
			return $path;
		else
			throw new \RuntimeException("File '$include' doesn't exist for inclusion in section [$section] from $origin");
	}
}
