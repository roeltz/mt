<?php

namespace data\core\exception;

class InvalidCollectionException extends QueryException {
		
	function __construct($collectionName) {
		parent::__construct("Collection '$collectionName' doesn't exist");
	}
}
