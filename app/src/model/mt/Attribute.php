<?php

namespace mt;

class Attribute {
	public $entity;
	public $name;
	public $type;
	public $nullable;
	public $unsigned;
	public $autoincrement;
	public $defaultValue;
	public $comments;
	public $meta;

	function __construct($name, $type, $nullable = true, $unsigned = false, $autoincrement = false, $defaultValue = null, $comments = null) {
		$this->name = $name;
		$this->type = $type;
		$this->nullable = $nullable;
		$this->unsigned = $unsigned;
		$this->autoincrement = $autoincrement;
		$this->comments = $comments;
		$this->meta = Meta::parse($this->comments);
		$this->defaultValue = $defaultValue;
	}

	function isPrimaryKey() {
		return $this->entity->isPrimaryKey($this);
	}

	function isForeignKey() {
		return $this->entity->isForeignKey($this);
	}
}