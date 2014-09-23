<?php

namespace mt;

class Entity {
	public $model;
	public $name;
	public $pk;
	public $comments;
	public $attributes = array();
	public $relations = array();
	public $uniqueTuples = array();
	public $meta;

	function __construct($name, $comments = null) {
		$this->name = $name;
		$this->comments = $comments;
		$this->meta = Meta::parse($this->comments);
	}

	function addAttribute(Attribute $attribute) {
		$this->attributes[$attribute->name] = $attribute;
		$attribute->entity = $this;
	}

	function addUniqueTuple(array $attributes) {
		$this->uniqueTuples[] = $attributes;
	}

	function setPrimaryKey(array $fields) {
		$this->pk = $fields;
	}

	function addRelation(Relation $relation) {
		$this->relations[] = $relation;
		$relation->localEntity = $this;
	}

	function isPrimaryKey(Attribute $attribute) {
		return in_array($attribute->name, $this->pk);
	}

	function isForeignKey(Attribute $attribute) {
		foreach($this->relations as $relation) {
			if (in_array($attribute->name, array_keys($relation->keys)))
				return $relation;
		}
		return false;
	}

}
