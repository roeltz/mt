<?php

namespace entity\validation;
use Exception;

class ValidationException extends Exception {
	
	private $errors;
	
	function __construct(array $errors) {
		$this->errors = $errors;
		parent::__construct($this->composeErrorMessage());
	}
	
	function getErrors() {
		return $this->errors;
	}
	
	private function composeErrorMessage() {
		return count(array_flatten($this->errors)) . " validation error(s), first error message for field '". reset(array_keys($this->errors)) ."': " . reset(reset($this->errors));
	}
}
