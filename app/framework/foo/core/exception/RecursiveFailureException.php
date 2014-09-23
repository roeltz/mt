<?php

namespace foo\core\exception;
use foo\core\Dispatch;

class RecursiveFailureException extends \Exception {
	function __construct(Dispatch $previous, Dispatch $current, $code = 0, $previous = null) {
		parent::__construct("For URI '{$current->request->uri}': ".
		"exception A ('{$previous->response->exception->getMessage()}') has caused ".
		"exception B ('{$current->response->exception->getMessage()}')", $code, $previous);
	}
}
