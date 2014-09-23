<?php

namespace foo\core\input;
use foo\core\Input;

class DummyInput implements Input {
	
	private $data;
	private $headers;
	private $userAgent;
	private $uri;
	private $source;
	
	function __construct($data = null, $headers = null, $userAgent = null, $uri = null, $source = null) {
		$this->data = $data;
		$this->headers = $headers;
		$this->userAgent = $userAgent;
		$this->uri = $uri;
		$this->source = $source;
	}
	
	function getURI() {
		return $this->uri;
	}
	function getSource() {
		return $this->source;
	}
	function getData() {
		return $this->data;
	}
	function getHeaders() {
		return $this->headers ? $this->headers : array();
	}
	function getUserAgent() {
		return $this->userAgent;
	}	
}
