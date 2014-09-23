<?php

namespace foo\core\view;

class FilterChain {
	
	protected $filters = array();
	
	function addFilter(Filter $filter) {
		$this->filters[] = $filter;
		return $this;
	}
	
	function process($buffer) {
		foreach($this->filters as $filter)
			$buffer = $filter->process($buffer);
		return $buffer;
	} 
}
