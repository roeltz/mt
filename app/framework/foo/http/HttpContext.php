<?php

namespace foo\http;
use foo\core\Context;
use foo\core\Input;
use foo\core\Router;
use foo\core\View;
use foo\core\Session;
use foo\core\session\PhpSession;
use foo\core\pipeline as pipes;
use foo\util\Pipeline;
use foo\http\view\PhpView;

class HttpContext extends Context {
	
	function getBasePath() {
		if (!defined('CFG_HTTP_BASE_URL'))
			define('CFG_HTTP_BASE_URL', "http" . (@$_SERVER['HTTPS'] == "on" ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . fnn(@constant('CFG_HTTP_BASE_CONTEXT'), "/"));
		return CFG_HTTP_BASE_URL;
	}

	function getPipelineInstance() {
		$pipeline = new Pipeline();
		$pipeline
			->appendPipe("routing", new pipes\Routing())
			->appendPipe("processing", new pipes\Processing())
			->appendPipe("view", new pipes\View());
		return $pipeline;
	}

	function getRequestInstance(
				Router $router,
				Input $input,
				Session $session = null) {

		if (!$session) $session = new PhpSession();
		return new HttpRequest($router, $input, $session);
	}
					
	function getResponseInstance(View $view = null) {
		return new HttpResponse($view);
	}
	
	static function httpDispatch(
				Router $router,
				View $view = null,
				Input $input = null,
				Session $session = null) {
		if (!$input) $input = new HttpInput();
		if (!$view) $view = new PhpView();
		return parent::dispatch($router, $input, $view, $session);
	}
}
