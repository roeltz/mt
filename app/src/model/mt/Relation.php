<?php

namespace mt;

class Relation {
	
	const NO_ACTION = "no-action";
	const CASCADE = "cascade";
	const RESTRICT = "restrict";
	const SET_NULL = "set-null";
	
	public $localEntity;
	public $foreignEntityName;
	public $keys = array();
	public $deletion;
	public $update;
	public $comments;
	public $meta;
	
	function __construct($foreignEntityName, $comments, $deletion = Relation::RESTRICT, $update = Relation::CASCADE) {
		$this->foreignEntityName = $foreignEntityName;
		$this->deletion = $deletion;
		$this->update = $update;
		$this->comments = $comments;
		$this->meta = Meta::parse($this->comments);
	}
	
	function addKey($localAttribute, $foreignAttribute) {
		$this->keys[$localAttribute] = $foreignAttribute;
	}
	
	function hasCompoundKey() {
		return count($this->keys) > 1;
	}
	
	function isNullable() {
		foreach($this->keys as $key=>$x)
			if ($this->localEntity->attributes[$key]->nullable)
				return true;
		return false;
	}
}
