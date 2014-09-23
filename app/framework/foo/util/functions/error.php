<?php

function recoverable_error_handler($errno, $errstr, $errfile, $errline) {
	if (E_RECOVERABLE_ERROR === $errno)
		throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
	return false;
}

set_error_handler('recoverable_error_handler');
