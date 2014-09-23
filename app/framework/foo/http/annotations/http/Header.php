<?php

namespace foo\http\annotations\http;
use foo\core\annotations\req\Option;

class Header extends Option {
	public $name = 'httpHeader';
}
