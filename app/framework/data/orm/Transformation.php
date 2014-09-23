<?php

namespace data\orm;

interface Transformation {
	function apply($value, $params, $isUpdate = false);
	function undo($value, $params);
}
