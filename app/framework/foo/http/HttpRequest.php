<?php

namespace foo\http;
use foo\core\Dispatch;
use foo\core\Request;

class HttpRequest extends Request {
	
	function getAuthDigest() {
		return $_SERVER['PHP_AUTH_DIGEST'];
	}
	
	function getAuthType() {
		return $_SERVER['AUTH_TYPE'];
	}
	
	function getAuthUser() {
		return $_SERVER['PHP_AUTH_USER'];
	}

	function getAuthPassword() {
		return $_SERVER['PHP_AUTH_PW'];
	}
	
	function getClientAddress() {
		return $_SERVER['REMOTE_ADDR'];
	}

	function getServerAddress() {
		return $_SERVER['SERVER_ADDR'];
	}
	
	function isHTTPS() {
		return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
	}
}
