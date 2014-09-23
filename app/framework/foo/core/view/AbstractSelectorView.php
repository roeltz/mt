<?php

namespace foo\core\view;
use foo\core\Dispatch;
use foo\core\View;
use foo\core\ViewSelector;

abstract class AbstractSelectorView implements View, ViewSelector {
	
	function render(Dispatch $dispatch) {
		if ($view = $this->resolve($dispatch))
			return $view->render($dispatch);
	}	
}
