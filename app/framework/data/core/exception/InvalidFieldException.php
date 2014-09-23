<?php

namespace data\core\exception;

class InvalidFieldException extends QueryException {
		
	function __construct($fieldName) {
		parent::__construct("Field '$fieldName' doesn't exist");
	}
}
