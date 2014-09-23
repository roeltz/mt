<?php

namespace data\orm\cache;
use foo\util\cache\Cache;

abstract class RecordCache {
	
	static function get($instance) {
		return Cache::get("memory")->get(get_class($instance), spl_object_hash($instance));
	}
	
	static function set($instance, array $record) {
		return Cache::get("memory")->set(get_class($instance), spl_object_hash($instance), $record);
	}
	
	static function remove($instance) {
		return Cache::get("memory")->remove(get_class($instance), spl_object_hash($instance));
	}	
}
