<?php

namespace data\core;

class Field {
	public $name;
	public $collection;
	
	function __construct($name, Collection $collection) {
		$this->name = $name;
		$this->collection = $collection;
	}
	
	function __toString() {
		$field = $this->name;
		$collection = $this->collection->alias
						? $this->collection->alias
						: $this->collection->name;
		return "{$collection}.{$field}";
	}
}
