<?php

// Taken from http://www.php.net/manual/en/function.glob.php#106595
function rglob($pattern, $flags = 0) {
	$files = glob($pattern, $flags);
	foreach (glob(dirname($pattern).DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
		$files = array_merge($files, rglob($dir.DIRECTORY_SEPARATOR.basename($pattern), $flags));
	return $files;
}
