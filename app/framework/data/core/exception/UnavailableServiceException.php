<?php

namespace data\core\exception;

class UnavailableServiceException extends ConnectionException {
	
	function __construct() {
		parent::__construct("Unavailable service");
	}
}
