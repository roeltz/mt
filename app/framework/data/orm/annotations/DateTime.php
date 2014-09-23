<?php

namespace data\orm\annotations;

/** @Target("property") */
class DateTime extends \Annotation {
	public $dateOnly = false;
	public $timeOnly = false;
	public $ignoreTimezone = false;
}
