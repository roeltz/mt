<?php

namespace foo\util\cache\sources;
use foo\util\cache\CacheSource;

class InMemoryCacheSource implements CacheSource {
	protected $cache = array();
	
	function has($category, $id) {
		return isset($this->cache[$category][$id]);
	}
	
	function get($category, $id) {
		return @$this->cache[$category][$id];
	}
	
	function set($category, $id, $value) {
		$this->cache[$category][$id] = $value;
	}
	
	function remove($category, $id) {
		unset($this->cache[$category][$id]);
	}
}
