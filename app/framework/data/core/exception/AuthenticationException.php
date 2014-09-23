<?php

namespace data\core\exception;

class AuthenticationException extends ConnectionException {
		
	function __construct() {
		parent::__construct("Invalid data source credentials");
	}
}
