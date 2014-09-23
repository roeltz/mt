<?php

namespace data\orm\annotations;

/** @Target("class") */
class Discriminate extends \Annotation {
	public $classes;
	public $property;
	public $classAsValue = false;
}
