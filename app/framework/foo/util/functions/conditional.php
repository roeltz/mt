<?php

function fnn() {
    $args = func_get_args();
    foreach ($args as $a)
        if (!is_null($a))
            return $a;
    return null;
}

function fne() {
    $args = func_get_args();
    foreach ($args as $a)
        if (!empty($a))
            return $a;
    return end($args);
}

function cval($cond, $value) {
	if ($cond) return $value;
}

function cecho($cond, $value) {
	if ($cond) echo $value;
}
