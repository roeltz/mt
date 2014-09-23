<?php

namespace data\orm\cache;
use foo\util\cache\Cache;
use data\orm\exception\ORMException;

abstract class TransformationsCache {
	
	static function get($filter) {
		$t = Cache::get("memory")->get("data\\orm\\Transformation", $filter);
		if (is_string($t))
			Cache::get("memory")->set("data\\orm\\Transformation", $filter, $t = new $t);
		elseif ($t)
			return $t;
		else
			throw new ORMException(__("Unregistered transformation '{filter}'", compact('filter')));
		return $t;
	}
	
	static function set($filter, $class) {
		return Cache::get("memory")->set("data\\orm\\Transformation", $filter, $class);
	}
}
