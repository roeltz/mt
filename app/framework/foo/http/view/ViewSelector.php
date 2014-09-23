<?php

namespace foo\http\view;
use foo\core\exception\ViewException;
use foo\core\Dispatch;
use foo\core\View;
use foo\core\view\AbstractSelectorView;

class ViewSelector extends AbstractSelectorView {
	
	const RULE_REGEX = '/^(\w+):(.+)$/';
	protected $rules;
	protected static $matchers = array();
	
	function __construct(array $rules) {
		foreach($rules as $rule=>$view)
			if ($rule != "default")
				$this->check($rule);
		
		$this->rules = $rules;
	}
	
	static function register($id, $pattern, $callback = null) {
		if (is_callable($pattern))
			self::$matchers[$id] = array('pattern'=>false, 'callback'=>$pattern);
		else
			self::$matchers[$id] = array('pattern'=>$pattern, 'callback'=>$callback);
	}
	
	function check($rule) {
		if (preg_match(self::RULE_REGEX, $rule, $m)) {
			list(, $matcher, $argument) = $m;
			if (isset(self::$matchers[$matcher])) {
				if (!($pattern = self::$matchers[$matcher]['pattern']) || preg_match($pattern, $argument))
					return true;
				elseif ($pattern)
					throw new ViewException(__("View selection rule '{matcher}' doesn't comply with the defined syntax", compact('matcher')));
			} else
				throw new ViewException(__("View selection matcher '{matcher}' isn't registered", compact('matcher')));
		} else
			throw new ViewException(__("View selection rule '{rule}' doesn't look like one", compact('rule')));
	}
	
	function resolve(Dispatch $dispatch) {
		$selectedView = null;
		
		foreach($this->rules as $rule=>$view) {
			if ($rule == "default") continue;
			
			preg_match(self::RULE_REGEX, $rule, $m);

			list(, $matcher, $argument) = $m;
			$pattern = self::$matchers[$matcher]['pattern'];
			$callback = self::$matchers[$matcher]['callback'];

			if ($pattern)
				preg_match($pattern, $argument, $argument);

			if ($callback($dispatch, $argument)) {
				$selectedView = $view;
				break;
			}
		}
		
		if (!$selectedView)
			$selectedView = @$this->rules['default'];
		
		if ($selectedView)
			return ($selectedView instanceof View) ? $selectedView : new $selectedView;
	}
}
