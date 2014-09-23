<?php

namespace foo\cli;
use foo\core\Context;
use foo\core\Router;
use foo\core\View;
use foo\core\Session;
use foo\core\pipeline as pipes;
use foo\util\Pipeline;

class CliContext extends Context {

	function getPipelineInstance() {
		$pipeline = new Pipeline();
		$pipeline
			->appendPipe("routing", new pipes\Routing())
			->appendPipe("processing", new pipes\Processing());
		return $pipeline;
	}
	
	function getBasePath() {
		return getcwd() . "/";
	}
	
	static function cliDispatch(
				Router $router,
				Input $input = null,
				Session $session = null) {
		if (!$input) $input = new CliInput();
		return parent::dispatch($router, $input, null, $session);
	}
}
