<?php

namespace foo\core\pipeline;
use foo\util\Pipe;
use foo\util\Pipeline;

class View implements Pipe {
		
	function run(Pipeline $pipeline, $dispatch) {
		$dispatch->response->render($dispatch);
	}
	
}
