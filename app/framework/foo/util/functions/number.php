<?php

function fit($n, $a, $b) {
    return ($n < $a ? $a : ($n > $b ? $b : $n));
}

function snap($n, $s) {
	return round($n / $s) * $s;
}

function between($n, $a, $b, $open = false) {
	return $open ? ($n > $a && $n < $b) : ($n >= $a && $n <= $b);
}

function outside($n, $a, $b) {
	return $n < $a || $n > $b;
}

