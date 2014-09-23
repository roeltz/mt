<?php

namespace foo\http;

class HttpInput implements \foo\core\Input {
	
	private $parser;
	
	function __construct(Parser $parser = null) {
		$this->parser = $parser ? $parser : new parser\DefaultParser();
	}
	
	function getURI() {
		$protocol = (@$_SERVER['HTTPS'] == "on") ? "https" : "http";
		$host = fne(@$_SERVER['HTTP_HOST'], @$_SERVER['SERVER_NAME']);
		$path = @$_SERVER['REQUEST_URI'];
		return "$protocol://$host$path";
	}
	
	function getSource() {
		return @$_SERVER['REQUEST_METHOD'];
	}
	
	function getData() {
		return $this->parser->getData();
	}
	
	function getHeaders() {
		return getallheaders();
	}
	
	function getUserAgent() {
		return @$_SERVER['HTTP_USER_AGENT'];
	}	
	
}
