<?php

namespace foo\util;

class HttpRequest {
	const METHOD_GET = "GET";
	const METHOD_POST = "POST";
	const METHOD_PUT = "PUT";
	const METHOD_DELETE = "DELETE";
	
	protected $method;
	protected $url;
	protected $data;
	protected $requestHeaders;

	protected $responseCode;
	protected $responseHeaders;
	protected $responseBody;
	protected $responseFilter;
	
	function __construct($method, $url, $data = null, array $headers = null) {
		$this->method = strtoupper($method);
		$this->url = new URL($url);
		$this->data = $data;
		$this->requestHeaders = array(
			'Accept'=>'text/html,*/*;q=0.9',
			'Host'=>$this->url->host,
			'User-Agent'=>'Foo HttpRequest'
		);
		if ($headers)
			$this->requestHeaders = array_merge($this->requestHeaders, $headers);
	}
	
	static function get($url, $data = null, array $headers = null) {
		return new self(self::METHOD_GET, $url, $data, $headers);
	}

	static function post($url, $data = null, array $headers = null) {
		return new self(self::METHOD_POST, $url, $data, $headers);
	}
	
	function send() {
		$this->responseHeaders = array();
		$this->responseBody = "";
		
		$url = clone $this->url;
		$ch = curl_init($url);
		
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		
		switch($this->method) {

			case self::METHOD_GET:
				curl_setopt($ch, CURLOPT_HTTPGET, 1);
				if ($this->data) {
					$url->addQuery($this->data);
					curl_setopt($ch, CURLOPT_URL, $url);
				}
				break;

			case self::METHOD_POST:
				curl_setopt($ch, CURLOPT_POST, 1);
				if ($this->data)
					curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
				break;

			default:
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
		}

		$flatHeaders = array();
		foreach($this->requestHeaders as $header=>$value)
			$flatHeaders[] = "$header: $value";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $flatHeaders);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($ch);
		$this->responseCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);
		
		if ($errno) {
			$this->responseCode = 400;
			return false;
		} else {
			list($headers, $this->responseBody) = explode("\r\n\r\n", $response, 2);

			$this->responseHeaders = array();
			foreach(array_slice(explode("\r\n", $headers), 1) as $line) {
				list($header, $value) = explode(":", $line, 2);
				$this->responseHeaders[trim($header)] = trim($value);
			}
	
			if ($filter = $this->responseFilter)
				$this->responseBody = $filter($this->responseBody);
			
			return $this;
		}
	}

	function getResponseCode() {
		return $this->responseCode;
	}

	function getResponseHeaders() {
		return $this->responseHeaders;
	}
	
	function getResponseBody() {
		return $this->responseBody;
	}
	
	function isSuccessful() {
		return $this->responseCode && $this->responseCode < 400;
	}
	
	function setResponseFilter($callable) {
		$this->responseFilter = $callable;
		return $this;
	}
	
	function __toString() {
		return $this->responseBody;
	}
}
