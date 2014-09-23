<?php

function object_walk_recursive($callback, &$object, $k = null, &$source = null, array &$visited = null) {
	if (!$visited) $visited = array();
	if (is_object($object) && in_array($object, $visited, true)) return;
	elseif (is_object($object)) $visited[] = $object;
	
	$result = $callback($object, $k, null);

	if ($result !== false && (is_object($object) || is_array($object)))
		foreach($object as $k=>&$v)
			object_walk_recursive($callback, $v, $k, $object, $visited);
}
