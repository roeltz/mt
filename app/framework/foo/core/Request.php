<?php

namespace foo\core;

class Request {
	
	private static $profiles = array();
	
	public $router;
	public $input;
	public $session;
	
	public $uri;
	public $source;
	public $data;
	public $headers;
	public $userAgent;
	public $profile = "default";
	public $route;
	
	function __construct(Router $router, Input $input, Session $session) {
		$this->router = $router;
		$this->session = $session;
		
		$this->uri = $input->getURI();
		$this->source = $input->getSource();
		$this->data = $input->getData();
		$this->headers = $input->getHeaders();
		$this->userAgent = $input->getUserAgent();
	}
	
	static function setProfile($context, $id, $pattern) {
		self::$profiles[$context][$id] = $pattern;
	}
	
	function resolveProfile(Context $context) {
		$class = get_class($context);
		foreach((array) @self::$profiles[$class] as $profile=>$pattern)
			if (preg_match($pattern, $this->userAgent))
				$this->profile = $profile;
	}
	
}
