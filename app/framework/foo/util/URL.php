<?php

namespace foo\util;

class URL {
	public $scheme;
	public $host;
	public $port;
	public $user;
	public $password;
	public $path;
	public $query = array();
	public $fragment;
	
	function __construct($url) {
		$components = parse_url($url);
		$this->scheme = $components['scheme'];
		$this->host = $components['host'];
		$this->port = @$components['port'];
		$this->user = @$components['user'];
		$this->password = @$components['password'];
		if (isset($components['path']))
			$this->path = $components['path'];
		else
			$this->path = "/";
		$this->fragment = @$components['fragment'];
		if (isset($components['query']))
			parse_str($components['query'], $this->query);
	}
	
	function addQuery(array $query) {
		$this->query = array_merge_recursive($this->query, $query);
	}
	
	function addQueryParameter($name, $value) {
		parse_str("$name=$value", $query);
		$this->addQuery($query);
	}
	
	function __toString() {
		$url = "{$this->scheme}://";
		if ($this->user) {
			$url .= $this->user;
			if ($this->password)
				$url .= ":{$this->password}";
			$url .= "@";
		}
		$url .= $this->host;
		$url .= $this->path ? $this->path : "/";
		if ($this->query)
			$url .= "?" . http_build_query($this->query);
		if ($this->fragment)
			$url .= "#{$this->fragment}";
		return $url;
	}
}
