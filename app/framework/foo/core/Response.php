<?php

namespace foo\core;

class Response {
		
	public $view;
	public $result;
	public $exception;
	
	function __construct(View $view = null) {
		$this->view = $view;
	}
	
	function render(Dispatch $dispatch) {
		if ($this->view instanceof ViewSelector)
			$this->view = $this->view->resolve($dispatch);
		$dispatch->events->trigger("view-is-known");
		if ($this->view && !@$dispatch->response->result->options['noView'])
			$this->view->render($dispatch);
	}
}
