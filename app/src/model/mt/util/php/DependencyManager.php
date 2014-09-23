<?php

namespace mt\util\php;

class DependencyManager {
	public $dependencies = array();

	function add($type) {
		if ($type instanceof Type)
			return $this->addType($type);
		else
			return $this->addFQN($type);
	}

	function addType(Type $type) {
		return $this->addFQN("{$type->namespace}\\{$type->name}");
	}

	function addFQN($fqn) {
		if (array_search($fqn, $this->dependencies))
			return $this->getFQNAlias($fqn);

		$segments = explode("\\", $fqn);
		$name = array_pop($segments);
		$namespace = join("\\", $segments);
		$this->dependencies[$alias = $this->getAvailableAliasedName($name, $namespace)] = $fqn;
		$this->sort();
		return $alias;
	}

	function getAlias($type) {
		if ($type instanceof Type)
			return $this->getTypeAlias($type);
		else
			return $this->getFQNAlias($type);
	}

	function getTypeAlias(Type $type) {
		return $this->getFQNAlias("{$type->namespace}\\{$type->name}");
	}

	function getFQNAlias($fqn) {
		return array_search($fqn, $this->dependencies);
	}

	function getAvailableAliasedName($name, $namespace) {
		if (!$namespace || $this->isAvailable($name, $name, $namespace)) {
			return $name;
		} else {
			$prefix = "";
			foreach(explode("\\", $namespace) as $i=>$ns) {
				$prefix .= ucfirst($ns);
				$alias = "$prefix$name";
				if ($this->isAvailable($alias, $name, $namespace))
					return $alias;
			}
		}
	}

	function isAvailable($alias, $name, $namespace) {
		return !isset($this->dependencies[$alias]) || ($this->dependencies[$alias] == "$namespace\\$name");
	}

	protected function sort() {
		asort($this->dependencies);
	}
}
