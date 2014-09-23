<?php

namespace foo\core;
use foo\util\ConstantSettings;
use foo\util\Registry;
use foo\core\router\ForwardRouter;
use foo\core\input\DummyInput;
use foo\core\session\PhpSession;

abstract class Context {
	
	private static $instances = array();
	private $inited;
	
	abstract function getBasePath();
	abstract function getPipelineInstance();
	
	final private function __construct() {
		if (!$this->inited) {
			foreach($this->getEventCallbacks("context", "init") as $callback)
				$callback($this);
			$this->inited = true;
		}
	}

	static function get() {
		$class = get_called_class();
		if (!isset(self::$instances[$class]))
			self::$instances[$class] = new $class();
		return self::$instances[$class];
	}
	
	static function attach($event, $callback) {
		list($category, $event) = explode(":", $event);
		return Registry::addByClass(get_called_class(), "event:$category", $event, $callback);
	}
	
	static function getEventCallbacks($category = "default", $event = null) {
		return Registry::getByClass(get_called_class(), "event:$category", $event, true);
	}
	
	static function hook($_) {
		foreach(func_get_args() as $hook)
			Importer::hook($hook);
	}
	
	function getRequestInstance(
				Router $router,
				Input $input,
				Session $session = null) {

		if (!$session) $session = new PhpSession();
		return new Request($router, $input, $session);
	}
	
	function getResponseInstance(View $view = null) {
		return new Response($view);
	}
	
	function getDispatchInstance(
				Router $router = null,
				Input $input = null,
				View $view = null,
				Session $session = null,
				Dispatch $previous = null) {
					
		if (!$router) $router = new ForwardRouter(CFG_ROUTING_DEFAULT_CONTROLLER, CFG_ROUTING_DEFAULT_METHOD);
		if (!$input) $input = new DummyInput();

		$dispatch = new Dispatch($this, $router, $input, $view, $session, $previous);
		foreach($this->getEventCallbacks("dispatch") as $event=>$callbacks)
			foreach($callbacks as $callback)
				$dispatch->events->bind($event, $callback);
		
		return $dispatch;
	}

	protected static function dispatch(
				Router $router = null,
				Input $input = null,
				View $view = null,
				Session $session = null) {
		
		$class = get_called_class();
		$context = new $class;
		return $context->getDispatchInstance($router, $input, $view, $session)->run();
	}

	function sub(Dispatch $previous, Route $route, $data = null, View $view = null, Session $session = null) {
		return $this->getDispatchInstance(
			$route,
			new DummyInput($data, $previous->request->headers, $previous->request->userAgent, $previous->request->uri, $previous->request->source),
			fnn($view, $previous->response->view),
			fnn($session, $previous->request->session),
			$previous
		)->run();
	}				

}
