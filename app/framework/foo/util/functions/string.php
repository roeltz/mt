<?php

function interpolate($str, $callback) {
	return preg_replace_callback('/\{([^}]+)\}/', function($m) use($callback){
		return $callback($m[1]);
	}, $str);
}

function fill($str, array $values) {
	return interpolate($str, function($key) use(&$values) {
		return @$values[$key];
	}, $str);
}

function tr($val, array $dict, $default = null) {
	echo @$dict[fnn($val, $default)];
}

function plural($n, $single, $plural, $showN = true) {
	return ($showN ? "$n " : "") . (($n == 1) ? $single : $plural);
}

function from_camel_case($str, $char = '-') {
    $str[0] = strtolower($str[0]);
    $func = create_function('$c', 'return "'. $char . '" . strtolower($c[1]);');
    return preg_replace_callback('/([A-Z])/', $func, $str);
}

function to_camel_case($str, $capitalise_first_char = false) {
    if ($capitalise_first_char) {
        $str[0] = strtoupper($str[0]);
    }
    $func = create_function('$c', 'return strtoupper($c[1]);');
    return preg_replace_callback('/[_-]([a-z])/', $func, $str);
}

function get_file_output($___path, array $___scope) {
	ob_start();
	foreach($___scope as $___k=>$___v)
		$$___k = $___v;
	unset($___scope, $___k, $___v);
	include $___path;
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}
