<?php

namespace mt\output;
use mt\Output;
use mt\Model;

class BufferOutput implements Output {
	
	private $buffer = "";

	function append($string) {
		$this->buffer .= $string;
		return $this;
	}
	
	function getData() {
		return $this->buffer;
	}
	
	function doStandardOutput() {
		echo $this->buffer;
		return $this;
	}
	
	function saveTo($path) {
		file_put_contents($path, $this->buffer);
		return $this;
	}
} 