<?php

namespace foo\core\pipeline;
use foo\util\Pipe;
use foo\util\Pipeline;

class Callback implements Pipe {
	
	public $callable;
	
	function __construct($callable) {
		$this->callable = $callable;
	}
	
	function run(Pipeline $pipeline, $context) {
		$callable = $this->callable;
		$callable($pipeline);
	}
}
