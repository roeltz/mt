<?php

function htmlselect($attr, array $options, $value = null, $nullOption = null) {
	$value = to_array($value);
	$attr = join(' ', array_map(function($a, $k){ return $a === true ? "$k" : "$k=\"$a\""; }, $attr, array_keys($attr)));
	$options = join("\n", array_map(function($o, $k) use($value) { $sel = in_array($k, $value) ? ' selected="selected"' : ''; return "<option value=\"$k\"$sel>$o</option>"; }, $options, array_keys($options)));
	if ($nullOption)
		$options = "<option value=\"\">$nullOption</option>$options";
	echo "<select $attr>\n$options\n</select>";
}

function v2ov(array $array) {
	$array2 = array();
	foreach($array as $v)
		$array2[$v] = $v;
	return $array2;
}

function array2options(array $array, $value, $text) {
	$options = array();
	foreach($array as $i) {
		$i = (object) $i;
		$options[$i->$value] = $i->$text;
	}
	return $options;
}

function htmlcheckbox($attr, $checked) {
	$attr = join(' ', array_map(function($a, $k){ return "$k=\"$a\""; }, $attr, array_keys($attr)));
	$checked = $checked ? "checked=\"checked\"" : "";
	echo "<input type=\"checkbox\" $attr $checked>";
}
