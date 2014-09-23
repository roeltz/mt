<?php

namespace data\core\exception;

class DuplicateEntryException extends QueryException {
		
	function __construct($duplicateValue, $offendedIndex) {
		parent::__construct("Duplicate value '$duplicateValue' for key '$offendedIndex'");
	}
}
