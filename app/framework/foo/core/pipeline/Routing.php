<?php

namespace foo\core\pipeline;
use foo\util\Pipe;
use foo\util\Pipeline;

class Routing implements Pipe {
		
	function run(Pipeline $pipeline, $dispatch) {
		$dispatch->request->resolveProfile($dispatch->context);
		$route = $dispatch->request->route = $dispatch->request->router->resolve($dispatch);
		$route->validate();
	}
	
}
