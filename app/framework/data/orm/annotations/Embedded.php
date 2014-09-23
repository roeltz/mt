<?php

namespace data\orm\annotations;

/** @Target({"class", "property"}) */
class Embedded extends \Annotation {
	public $refOnly = false;
}
