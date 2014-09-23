<?php

namespace mt;

class Meta {
		
	static function parse(&$string) {
		$meta = array();
		preg_match_all('/@(\w+)(?:\s+(.+))?/', $string, $m);
		foreach($m[0] as $i=>$v)
			$meta[$m[1][$i]] = @$m[2][$i];
		$string = trim(preg_replace('/@\w+\s+.+/', "", $string));
		return $meta;
	}	
}
