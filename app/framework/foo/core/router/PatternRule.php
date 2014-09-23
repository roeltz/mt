<?php

namespace foo\core\router;

class PatternRule {
	public $route;
	public $pattern;
	public $source;
	public $profile;
	public $defaults;
	
	function __construct($route, $pattern, $source, $profile, array $defaults = null) {
		$this->route = $route;
		$this->pattern = $pattern;
		$this->source = $source;
		$this->profile = $profile;
		$this->defaults = $defaults;
	}
}
