<?php

namespace foo\core\view;
use foo\core\Dispatch;
use foo\core\ViewSelector;
use foo\core\view\AbstractSelectorView;
use foo\util\Pipeline;
use foo\util\Pipe;

class ViewSelectorPipe implements Pipe {
	private $viewSelector;
	
	function __construct(ViewSelector $viewSelector) {
		$this->viewSelector = $viewSelector;
	}
		
	function run(Pipeline $pipeline, $dispatch) {
		return $this->viewSelector->resolve($dispatch);
	}
}

class ViewSelectorRegistry extends AbstractSelectorView {
	
	protected static $instance;
	protected $pipeline;
	
	static function getInstance(ViewSelector $default = null) {
		if (!self::$instance)
			self::$instance = new self($default);
		elseif ($default)
			self::$instance->pipeline->appendPipe("default", new ViewSelectorPipe($default));
		return self::$instance;
	}
	
	static function append($id, ViewSelector $viewSelector) {
		self::getInstance();
		self::$instance->pipeline->appendPipe($id, new ViewSelectorPipe($viewSelector));
	}
	
	static function prepend($id, ViewSelector $viewSelector) {
		self::getInstance();
		self::$instance->pipeline->prependPipe($id, new ViewSelectorPipe($viewSelector));
	}

	static function before($id, $ref, ViewSelector $viewSelector) {
		self::getInstance();
		self::$instance->pipeline->prependPipe($id, $ref, new ViewSelectorPipe($viewSelector));
	}

	static function after($id, $ref, ViewSelector $viewSelector) {
		self::getInstance();
		self::$instance->pipeline->prependPipe($id, $ref, new ViewSelectorPipe($viewSelector));
	}
	
	protected function __construct(ViewSelector $default = null) {
		$this->pipeline = new Pipeline();
		if ($default)
			$this->pipeline->appendPipe("default", new ViewSelectorPipe($default));
	}
		
	function resolve(Dispatch $dispatch) {
		while($this->pipeline->isActive())
			if ($this->pipeline->step($dispatch))
				return $this->pipeline->getLastResult();
	}
}
