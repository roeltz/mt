<?php

namespace data\orm\cache;
use DateTime;
use foo\util\cache\Cache;
use data\orm\ORM;
use data\orm\Descriptor;

abstract class InstanceCache {
	
	static function get($class, $pk) {
		return Cache::get("memory")->get($class, self::pk($pk));
	}
	
	static function set($instance, $pk) {
		return Cache::get("memory")->set(get_class($instance), self::pk($pk), $instance);
	}
	
	static function remove($class, $pk) {
		return Cache::get("memory")->remove($class, self::pk($pk));
	}
	
	private static function pk($pk) {
		if (is_array($pk)) {
			$values = array();
			foreach($pk as $v)
				$values[] = self::pk($v);
			return join("+", $values);
		} elseif (ORM::isPersistible($pk)) {
			$descriptor = Descriptor::getInstance(get_class($pk));
			return self::pk(ORM::getPKValues($pk, true));
		} elseif ($pk instanceof DateTime)
			return $pk->format("Y-m-d H:i:s");
		else
			return (string) $pk;
	}
	
}
