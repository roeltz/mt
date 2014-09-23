<?php

use foo\core\Dispatch;
use foo\cli\CliContext;

ini_set("xdebug.show_exception_trace", 0);

CliContext::attach("dispatch:error", function(Dispatch $dispatch){
	$e = $dispatch->response->exception;
	$t = preg_replace('/' . preg_quote(getcwd() . DIRECTORY_SEPARATOR, '/') . '/', '', $e->getTraceAsString());
	echo "Uncaught exception " . get_class($e) . "\n{$e->getMessage()}\n\n$t";
});
