<?php

namespace mt;

interface Output {
	function getData();
	function doStandardOutput();
	function saveTo($path);
}
