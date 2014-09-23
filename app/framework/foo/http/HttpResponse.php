<?php

namespace foo\http;
use foo\core\Dispatch;
use foo\core\Response;

class HttpResponse extends Response {
	protected $isRedirected = false;
	
	function render(Dispatch $dispatch) {
		
		$options = &$dispatch->response->result->options;
		
		if ($code = @$options['httpStatusCode'])
			$this->setStatusCode($code, @$options['httpStatusMessage']);
		
		if (($cache = @$options['httpCache']) === false)
			$this->noCache();
		elseif (is_string($cache))
			$this->setExpiration($expression);
		
		if ($contentType = @$options['httpContentType'])
			$this->setContentType($contentType);

		if ($filename = @$options['httpDownload'])
			$this->setDownload($filename);
		
		if (@$headers = @$options['httpHeader'])
			foreach((array) $headers as $header)
				$this->setHeader($header);
			
		if ($origin = @$options['httpAllowOrigin'])
			$this->setAllowedOrigin($origin);

		if (!$this->isRedirected)
			parent::render($dispatch);
	}
	
    function setStatusCode($code, $message = null) {
    	header("{$_SERVER['SERVER_PROTOCOL']} $code $message");
    }

	function setHeader($header) {
		header($header);
	}

	function redirect($uri) {
		header("Location: $uri");
		$this->isRedirected = true;
	}
	
	function redirectLocal($uri = "") {
		$this->redirect(CFG_HTTP_BASE_URL . $uri);
	}

	function setContentType($contentType) {
		header("Content-Type: $contentType");
	}
	
	function setDownload($filename = "download") {
		header("Content-Disposition: attachment; filename=$filename");
	}
	
	function setExpiration($expression) {
		$date = new \DateTime($expression, new \DateTimeZone('UTC'));
		header("Expires: " . gmdate("D, d M Y H:i:s", $date->getTimestamp()));
	}

	function setAllowedOrigin($origin = "*") {
		$this->setHeader("Access-Control-Allow-Origin: $origin");
	}

	function noCache() {
		header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}
	
}
