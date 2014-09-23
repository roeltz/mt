<?php

namespace data\orm\annotations;

/** @Target("property") */
class OrderByDefault extends \Annotation {
	public $value = "asc";
}
