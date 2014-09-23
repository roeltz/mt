<?php

namespace foo\core\router;
use Exception;
use foo\core\Dispatch;
use foo\core\Router;
use foo\core\exception\RoutingException;
use foo\util\Pipeline;
use foo\util\Pipe;

class RouterPipe implements Pipe {
	private $router;
	
	function __construct(Router $router) {
		$this->router = $router;
	}
		
	function run(Pipeline $pipeline, $dispatch) {
		return $this->router->resolve($dispatch);
	}
}

class RouterRegistry implements Router {
	
	protected static $instance;
	protected $pipeline;
	
	static function getInstance(Router $default = null) {
		if (!self::$instance)
			self::$instance = new self($default);
		elseif ($default)
			self::$instance->pipeline->appendPipe("default", new RouterPipe($default));
		return self::$instance;
	}
	
	static function append($id, Router $router) {
		self::getInstance();
		self::$instance->pipeline->appendPipe($id, new RouterPipe($router));
	}
	
	static function prepend($id, Router $router) {
		self::getInstance();
		self::$instance->pipeline->prependPipe($id, new RouterPipe($router));
	}

	static function before($id, $ref, Router $router) {
		self::getInstance();
		self::$instance->pipeline->prependPipe($id, $ref, new RouterPipe($router));
	}

	static function after($id, $ref, Router $router) {
		self::getInstance();
		self::$instance->pipeline->prependPipe($id, $ref, new RouterPipe($router));
	}
	
	protected function __construct(Router $default = null) {
		$this->pipeline = new Pipeline();
		if ($default)
			$this->pipeline->appendPipe("default", new RouterPipe($default));
	}
		
	function resolve(Dispatch $dispatch) {
		while($this->pipeline->isActive())
			if ($this->pipeline->step($dispatch))
				if ($route = $this->pipeline->getLastResult())
					return $route;
				else {
					$ex = $this->pipeline->getLastException();
					if ($ex instanceof Exception && !($ex instanceof RoutingException))
						throw $ex;
				}
		throw new RoutingException(__("No route could be resolved"));
	}
}
