<?php

namespace data\orm\annotations;

/** @Target("property") */
class Id extends \Annotation {
	public $auto = true;
	public $sequence;
}
