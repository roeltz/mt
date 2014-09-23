<?php

namespace foo\http\annotations\http;
use foo\core\annotations\req\Option;
use foo\http\Response;
use foo\http\ResponseModifier;

class Cache extends Option {
	public $name = 'httpCache';
	public $value = "+1 hour";
}
