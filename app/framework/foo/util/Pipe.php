<?php

namespace foo\util;

interface Pipe {
	function run(Pipeline $pipeline, $context);
}
