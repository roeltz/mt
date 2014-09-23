<?php

namespace foo\core;
use foo\util\Pipeline;
use foo\util\Pipe;
use foo\util\EventSource;
use Exception;

class Dispatch {
	
	public $events;
	public $pipeline;
	
	public $context;
	public $request;
	public $response;
	public $previous;
	public $locale;
	
	function __construct(Context $context, Router $router, Input $input, View $view = null, Session $session = null, Dispatch $previous = null) {
		$this->events = new EventSource($this);
		$this->context = $context;
		$this->pipeline = $context->getPipelineInstance();
		$this->request = $context->getRequestInstance($router, $input, $session);
		$this->response = $context->getResponseInstance($view);
		$this->previous = $previous;
	}
	
	function run() {
		$this->events->trigger("init");
		while($this->pipeline->isActive()) {
			try {
				$pipe = $this->pipeline->getCurrentPipe();
				$this->events->trigger("pre-$pipe");
				if ($this->pipeline->step($this)) {
					$this->events->trigger("post-$pipe");
				} else {
					$this->pipeline->finish();
					$this->response->exception = $this->pipeline->getLastException();
					if ($this->events->trigger("$pipe-error"))
						$this->events->trigger("error");
				}
			} catch(Exception $e) {
				$this->pipeline->finish();
				$this->response->exception = $e;
				$this->events->trigger("error");
			}
		}
		$this->events->trigger("finish");
		return @$this->response->result->data;
	}
	
	function pipe($id, Pipe $pipe) {
		$this->pipeline->pipe($id, $pipe);
		return $this;
	}
	
	function forward(Route $route, $data = null, View $view = null, Session $session = null) {
		$this->context->sub($this, $route, $data, $view, $session);
		$this->terminate();
	}
	
	function terminate() {
		$this->pipeline->finish();
	}
}
