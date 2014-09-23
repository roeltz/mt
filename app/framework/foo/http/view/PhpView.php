<?php

namespace foo\http\view;
use foo\core\Dispatch;
use foo\core\Route;
use foo\core\View;
use foo\core\exception\ViewException;
use foo\core\view\Filter;
use foo\core\view\FilterChain;

class PhpViewContextHelper {
	private $dispatch;
	private $view;
	private $directory;
	private $file;
	
	function __construct(Dispatch $dispatch, View $view, $directory, $file) {
		$this->dispatch = $dispatch;
		$this->view = $view;
		$this->directory = $directory;
		$this->file = preg_match('/\.php$/', $file) ? $file : "$file.php";
	}
	
	function render($data, $options = null) {
		$options = (object) $options;

		if (is_array($data) || is_object($data))
			extract((array) $data);

		require "{$this->directory}/{$this->file}";
	}

	function put($file, $data = null) {
		$file = new PhpViewContextHelper($this->dispatch, $this->view, dirname("{$this->directory}/{$this->file}"), $file);
		$file->render($data);
	}
	
	function sub($route, $data = null) {
		list($controller, $method) = explode(":", $route);
		$this->dispatch->context->sub(
			$this->dispatch,
			new Route($controller, $method),
			$data,
			$this->view
		);
	}
}

class PhpView implements View {
	
	public $viewsDirectory;
	protected $filterChain;
	
	function __construct($_ = null) {
		foreach(func_get_args() as $arg) {
			if ($arg instanceof Filter)
				$this->addFilter($arg);
		}
	}
	
	private function getFilterChain() {
		if (!$this->filterChain)
			$this->filterChain = new FilterChain();
		return $this->filterChain;
	}
	
	function addFilter(Filter $filter) {
		$this->getFilterChain()->addFilter($filter);
		return $this;
	}
	
	function render(Dispatch $dispatch) {
		$data = $dispatch->response->result->data;
		$options = array_merge(
			array('viewsDirectory'=>CFG_VIEWS_DIRECTORY),
			$dispatch->response->result->options
		);
		
		if ($this->filterChain) {
			ob_start();
		}
		
		$helper = new PhpViewContextHelper($dispatch, $this, $options['viewsDirectory'], $this->getInputFile($options));
		$helper->render($data, $options);

		if ($this->filterChain) {
			$buffer = ob_get_contents();
			ob_end_clean();
			echo $this->filterChain->process($buffer);
		}
	}
	
	function getInputFile($options) {
		$file = fne(@$options['template'], @$options['view']);
		if ($file) {
			if (file_exists("{$options['viewsDirectory']}/$file.php")) {
				return $file;
			} else
				throw new ViewException(__("View resource '{file}' does not exists", compact('file')));
		} else {
			throw new ViewException(__("View option not set"));
		}
	}
}
