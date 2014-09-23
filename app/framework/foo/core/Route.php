<?php

namespace foo\core;
use ReflectionAnnotatedClass;
use ReflectionAnnotatedMethod;
use ReflectionParameter;
use ReflectionException;
use foo\core\exception\RoutingException;

class Route implements Router {
	private static $parameterResolvers = array();
	
	public $controller;
	public $method;
	public $source;
	public $profile;
	
	static function registerParameterResolver($callback) {
		self::$parameterResolvers[] = $callback;
	}
	
	function __construct($controller, $method, $source = "*", $profile = "*") {
		$this->controller = $controller;
		$this->method = $method;
		$this->source = $source;
		$this->profile = $profile;
	}
	
	function resolve(Dispatch $dispatch) {
		return $this;
	}
	
	function validate() {
		$controllerClass = $this->findController();
		$methodInstance = $this->findMethod($controllerClass, $this->method, $this->source, $this->profile);
	}
	
	function execute(Dispatch $dispatch) {
		$controllerClass = $this->findController();
		$controllerInstance = $controllerClass->newInstance();
		$methodInstance = $this->getReflectionMethod();
		$result = $methodInstance->invokeArgs($controllerInstance, $this->buildParametersList($dispatch, $methodInstance));
		return Result::from($result, $methodInstance);
	}
	
	function getReflectionMethod() {
		$controllerClass = $this->findController();
		return $this->findMethod($controllerClass, $this->method, $this->source, $this->profile);		
	}

	function getAnnotatedOptions() {
		return Result::getAnnotatedOptions($this->getReflectionMethod());		
	}
	
	protected function findController() {
		try {
			return new ReflectionAnnotatedClass($this->controller);
		} catch(ReflectionException $e) {
			throw new RoutingException("Route controller not found");
		}
	}
	
	protected function findMethod(ReflectionAnnotatedClass $controller, $methodName, $source, $profile) {
		foreach($controller->getMethods() as $method) {
			$mapping = $method->getAnnotation('req\\Mapping');
			$mappingAlias = $mapping ? $mapping->alias : null;
			$mappingSource = $mapping ? $mapping->source : "*";
			$mappingProfile = $mapping ? $mapping->profile : "*";

			if (($method->getName() == $methodName || $mappingAlias == $methodName)
				&& ($mappingSource == "*" || $mappingSource == $source)
				&& ($mappingProfile == "*" || $mappingProfile == $profile))
				return $method;
		}
		throw new RoutingException("Route method not found");
	}

	protected function buildParametersList(Dispatch $dispatch, ReflectionAnnotatedMethod $method) {
		$parameters = array();
		foreach($method->getParameters() as $parameter) {
			$paramName = $parameter->getName();
			$paramClass = $parameter->getClass();
			if (!is_null($value = @$dispatch->request->data[$paramName])) {
				$parameters[] = $this->castParameter($parameter, $value);
			} elseif ($parameter->isOptional()) {
				$parameters[] = $parameter->getDefaultValue();
			} elseif ($paramClass) {
				if ($paramClass->isInstance($dispatch)) {
					$parameters[] = $dispatch;
				} elseif ($paramClass->isInstance($dispatch->request)) {
					$parameters[] = $dispatch->request;
				} elseif ($paramClass->isInstance($dispatch->response)) {
					$parameters[] = $dispatch->response;
				} elseif ($paramClass->isInstance($dispatch->request->session)) {
					$parameters[] = $dispatch->request->session;
				} elseif ($paramClass->implementsInterface("foo\\core\\Principal")) {
					$parameters[] = $dispatch->request->session->getPrincipal();
				} elseif ($paramClass->isInstance($dispatch->context)) {
					$parameters[] = $dispatch->context;
				} else {
					foreach(self::$parameterResolvers as $callback)
						if ($result = $callback($parameter, $dispatch)) {
							$parameters[] = $result;
							continue 2;
						}
					$parameters[] = null;
				}
			} else
				$parameters[] = null;
		}
		return $parameters;
	}

	protected function castParameter(ReflectionParameter $parameter, $value) {
		if ($class = $parameter->getClass()) {
			if ($class->getName() == "DateTime") {
				if (is_numeric($value)) {
					$date = new \DateTime();
					$date->setTimestamp($value);
					return $date;
				} elseif (strlen($value))
					return new \DateTime($value);
				else
					return null;
			}
		} elseif (is_numeric($value)) {
			return (double) $value;
		}
		
		return $value;
	}	
}
