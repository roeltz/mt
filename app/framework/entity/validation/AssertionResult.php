<?php

namespace entity\validation;

class AssertionResult {
	public $success;
	public $message;
	public $values;
	
	function __construct($success, $message = null, $values = null) {
		if (!($this->success = $success)) {
			$this->message = $message;
			$this->values = $values;
		}
	}
}
