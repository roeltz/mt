<?php

namespace foo\core\annotations\req;
use foo\core\annotations\FooAnnotation;

class Secured extends FooAnnotation {
	public $value = true;
	public $allow = "*";
	public $deny = null;
	public $denyFirst = false;
}
