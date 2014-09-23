<?php

namespace foo\core\pipeline;
use foo\util\Pipe;
use foo\util\Pipeline;

class Processing implements Pipe {
		
	function run(Pipeline $pipeline, $dispatch) {
		$dispatch->response->result = $dispatch->request->route->execute($dispatch);
	}
	
}
