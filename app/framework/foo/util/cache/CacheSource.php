<?php

namespace foo\util\cache;

interface CacheSource {
	function has($category, $id);
	function get($category, $id);
	function set($category, $id, $value);
	function remove($category, $id);
}
