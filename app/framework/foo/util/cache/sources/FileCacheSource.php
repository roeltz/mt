<?php

namespace foo\util\cache\sources;
use foo\util\cache\CacheSource;

class FileCacheSource implements CacheSource {
	protected $basedir;
	
	function __construct($basedir = null) {
		if (!$basedir)
			$basedir = defined('CFG_CACHE_PATH') ? CFG_CACHE_PATH : ".";
		$this->basedir = $basedir;
	}
	
	function has($category, $id) {
		return file_exists($this->filename($category, $id));
	}
	
	function get($category, $id) {
		return unserialize(file_get_contents($this->filename($category, $id)));
	}
	
	function set($category, $id, $value) {
		file_put_contents($this->filename($category, $id), serialize($value));
	}
	
	function remove($category, $id) {
		unlink($this->filename($category, $id));
	}
	
	protected function filename($category, $id) {
		return "{$this->basedir}/$category-$id";
	}
}
