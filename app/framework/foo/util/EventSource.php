<?php

namespace foo\util;

class EventSource {
	
	private static $global;
	private $context;
	private $bindings = array();
	
	function __construct($context) {
		$this->context = $context;
	}
	
	static function getGlobal() {
		if (!self::$global)
			self::$global = new EventSource(new stdClass);
		return self::$global;
	}
	
	function bind($event, $callback) {
		$this->bindings[$event][] = $callback;
	}
	
	function trigger($event, &$data = null) {
		return $this->triggerContext($event, $this->context, $data);
	}
	
	function triggerContext($event, $context, &$data = null) {
		if ($callbacks = @$this->bindings[$event]) {
			foreach($callbacks as $callback) {
				$result = $callback($context, $data);
				if ($result === false)
					return false;
			}
		}
		return true;		
	}
}