<?php

namespace data\core;
use foo\util\cache\Cache;
use data\core\exception\ConnectionException;

abstract class Connections {
	
	static function get($id) {
		if ($ds = Cache::get("memory")->get("data\\core\\DataSource", $id)) {
			if (is_callable($ds))
				self::set($id, $ds = $ds());
			return $ds;
		} else
			throw new ConnectionException(__("DataSource '{id}' not set", compact('id')));
	}
	
	static function set($id, DataSource $instance) {
		return Cache::get("memory")->set("data\\core\\DataSource", $id, $instance);
	}
	
	static function setDelayed($id, $callback) {
		return Cache::get("memory")->set("data\\core\\DataSource", $id, $callback);
	}
}
