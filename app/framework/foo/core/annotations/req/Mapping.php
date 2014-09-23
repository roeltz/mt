<?php

namespace foo\core\annotations\req;
use foo\core\annotations\FooAnnotation;

class Mapping extends FooAnnotation {
	public $alias;
	public $source = "*";
	public $profile = "*";
	public $pattern;
}