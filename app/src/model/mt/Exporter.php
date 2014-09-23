<?php

namespace mt;

interface Exporter {
	function getOutput(Model $model);
	function getDefaultOuputPath($path);
}
