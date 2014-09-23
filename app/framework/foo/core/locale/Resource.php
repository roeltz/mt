<?php

namespace foo\core\locale;

abstract class Resource {
	protected $path;
	
	final function __construct($path) {
		$this->path = $path;
	}
	
	protected function getLocalizedPath($localeCode) {
		return interpolate($this->path, function($k) use($localeCode) { return $k == "locale" ? $localeCode : $k; });
	}
	
	abstract function getMessage($message, $localeCode);
}
