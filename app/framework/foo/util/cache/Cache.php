<?php

namespace foo\util\cache;

abstract class Cache {
	
	protected static $sources = array();
	
	static function get($id) {
		return @self::$sources[$id];
	}
	
	static function set($id, CacheSource $instance) {
		self::$sources[$id] = $instance;
	}
}
