<?php

namespace mt\util\php;

class Type {
	public $name;
	public $namespace;
	public $docBlock;
	public $constants = array();
	public $methods = array();
	public $parent;
	public $dependencies;

	function __construct($name, $namespace = null) {
		$this->name = $name;
		$this->namespace = $namespace;
		$this->docBlock = new DocBlock();
		$this->dependencies = new DependencyManager();
	}

	function inherit(Type $parent) {
		$this->parent = $parent;
		$this->dependencies->add($parent);
	}

	function addConstant(Constant $constant) {
		$this->constants[] = $constant;
	}

	function addMethod(Method $method) {
		$this->methods[] = $method;
		$method->type = $this;
		$method->dependencies = $this->dependencies;
	}

	function getFullyQualifiedName() {
		$fqn = $this->name;
		if ($this->namespace)
			$fqn = "{$this->namespace}\\$fqn";
		return $fqn;
	}

	function getRelativeName(Type $type, &$isFQN) {
		$fqn = $this->getFullyQualifiedName();
		$regex = '/^'.preg_quote($type->namespace).'\\\\/';
		if (preg_match($regex, $fqn)) {
			$isFQN = false;
			return preg_replace($regex, '', $fqn);
		} else {
			$isFQN = true;
			return $fqn;
		}
	}
}
