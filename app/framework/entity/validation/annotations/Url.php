<?php

namespace entity\validation\annotations;
use entity\validation\AnnotableAssertion;
use entity\validation\AssertionResult;

/** @Target("property") */
class Url extends AnnotableAssertion {
	
	public $scheme = true;
	public $host = true;
	public $path = false;
	public $queryString = false;
	
	function check($value, $property, $instance) {
		$options = 0;
		
		if ($this->scheme)
			$options = $options | FILTER_FLAG_SCHEME_REQUIRED;
		if ($this->host)
			$options = $options | FILTER_FLAG_HOST_REQUIRED;
		if ($this->path)
			$options = $options | FILTER_FLAG_PATH_REQUIRED;
		if ($this->queryString)
			$options = $options | FILTER_FLAG_QUERY_REQUIRED;
		
		return new AssertionResult(filter_var($value, FILTER_VALIDATE_URL, $options), fnn($this->message, "Not a valid URL"));
	}
}
