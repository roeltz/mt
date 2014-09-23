<?php

namespace foo\core\router;
use foo\core\Router;
use foo\core\Route;
use foo\core\Dispatch;
use foo\core\exception\RoutingException;

class PatternRouter implements Router {
	
	const PATTERN_EXPRESSION = '/^(?:\\[([^]]+)\\]\\s+)?(?:(\\w+)\\s+)?(.+)$/';
	
	static $defaultPatterns = array(
		'default'=>'[^\\/.]+',
		'word'=>'[\\w-]+',
		'int'=>'\\d+',
		'date'=>'\\d{4}-\\d{2}-\\d{2}'
	);
	
	public $rules = array();
	
	function __construct(array $rules = array()) {
		$this->addRules($rules);
	}
	
	function addRules(array $rules) {
		$this->rules = array_merge($this->rules, $this->parseRules($this->flattenRules($rules)));
	}
	
	protected function flattenRules(array $rules, $prefix = "") {
		$flat = array();
		foreach($rules as $pattern=>$route)
			if (is_array($route))
				$flat = array_merge($flat, $this->flattenRules($route, "$prefix$pattern"));
			else {
				if ($pattern) {
					preg_match(self::PATTERN_EXPRESSION, $pattern, $pm);
					list(, $profile, $source, $uri) = $pm;
				} else {
					list($profile, $source, $uri) = array(null, null, "/");
				}
					
				if ($profile) $profile = "$profile ";
				if ($source) $source = "$source ";
				if ($uri == "#") $uri = "";
				$flat["$profile$source$prefix$uri"] = $route;
			}
		return $flat;
	}
	
	protected function parseRules(array $rules) {
		
		$parsedRules = array();
		
		foreach($rules as $pattern=>$route) {
			
			if (preg_match(self::PATTERN_EXPRESSION, $pattern, $pm)) {
					
				list(, $profile, $source, $uri) = $pm;
				
				if ($uri == "/") $uri = "";
				if (!$source) $source = "*";
				if (!$profile) $profile = "*";

				@list($route, $defaults) = explode("?", $route);
				
				if ($defaults) {
					$defaultValues = array();
					foreach(explode("&", $defaults) as $parameter) {
						@list($var, $value) = explode("=", $parameter);
						$defaultValues[$var] = is_null($value) ? true : $value;
					}
				} else
					$defaultValues = null;
				
				$parsedRules[] = new PatternRule($route, $uri, $source, $profile, $defaultValues);
			
			} else {
				trigger_error("Syntax error in routing pattern '$pattern'", E_USER_WARNING);
			}
		}
		
		return $parsedRules;
	}
	
	protected function getURI(Dispatch $dispatch) {
		$escapedURI = preg_quote($dispatch->context->getBasePath(), '#');
		return current(explode('?', preg_replace("#^$escapedURI#", '', $dispatch->request->uri)));
	}

	protected function parsePattern($uriPattern) {
		$defaultPatterns = self::$defaultPatterns;
		$escapedPattern = preg_replace('#/#', '\\/', $uriPattern);
		$parameters = array();
		$regex = '/^' . preg_replace_callback('/\\{[^}]+\\}/', function($m) use(&$parameters, $defaultPatterns){
			@list($parameter, $pattern) = explode(":", trim($m[0], "{}"));
			$parameters[] = $parameter;
			if ($pattern && ($def = @$defaultPatterns[$pattern])) $pattern = $def;
			else $pattern = $defaultPatterns['default'];
			return "($pattern)";
		}, $escapedPattern) . '$/';
		return array($regex, $parameters);
	}
	
	protected function matchRule(PatternRule $rule, $uri, $source, $profile) {
		list($regex, $parameters) = $this->parsePattern($rule->pattern);

		if (preg_match($regex, $uri, $m)
			&& ($rule->source == "*" || $rule->source == $source)
			&& ($rule->profile == "*" || $rule->profile == $profile)) {
			
			$data = count($parameters)
						? array_combine($parameters, array_slice($m, 1))
						: array();
			
			@list($route, $defaults) = explode("?", $route);
			foreach(explode("&", $defaults) as $parameter) {
				@list($var, $value) = explode("=", $parameter);
				$data[$var] = is_null($value) ? true : $value;
			}
			
			$data = array_merge($data, (array) $rule->defaults);

			return $data;
		} else
			return false;
	}

	function resolve(Dispatch $dispatch) {
		
		$defaultPatterns = &self::$defaultPatterns;
		$uri = $this->getURI($dispatch);
		
		foreach($this->rules as $rule) {
			if ($data = $this->matchRule($rule, $uri, $dispatch->request->source, $dispatch->request->profile)) {
				foreach($data as $k=>$v)
					$dispatch->request->data[$k] = $v;
				
				list($controller, $method) = explode(":", $rule->route);
				$r = new Route($controller, $method, $dispatch->request->source, $dispatch->request->profile);
				return $r;
			}
		}

		throw new RoutingException(__("No route could be resolved"));
	}
}
