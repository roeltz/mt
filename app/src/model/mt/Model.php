<?php

namespace mt;

class Model {
	
	public $name;
	public $comments;
	public $entities = array();
	public $meta;
	
	function __construct($name, $comments) {
		$this->name = $name;
		$this->comments = $comments;
		$this->meta = Meta::parse($this->comments);
	}
	
	function addEntity(Entity $entity) {
		$this->entities[$entity->name] = $entity;
		$entity->model = $this;
	}	
}