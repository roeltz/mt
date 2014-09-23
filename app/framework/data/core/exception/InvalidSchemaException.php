<?php

namespace data\core\exception;

class InvalidSchemaException extends ConnectionException {
	
	function __construct($schemaName) {
		parent::__construct("Schema '$schemaName' doesn't exist");
	}
}
