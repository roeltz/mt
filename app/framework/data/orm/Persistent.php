<?php

namespace data\orm;
use data\core\Aggregate;

abstract class Persistent {
	
	function __construct() {
		if (!$this->getDescriptor()->embeddedClass && count($args = func_get_args()))
			$this->retrieve($args);
	}
	
	function __get($property) {
		$descriptor = $this->getDescriptor();
		if ($descriptor->isRelationToOne($property) || $descriptor->isRelationToMany($property))
			return $this->$property = $this->bring($property);
	}
	
	function retrieve($pk) {
		return ORM::retrieve($this, $pk);
	}
	
	function save($refresh = true) {
		return ORM::save($this, $refresh);
	}
	
	function update($refresh = true) {
		return ORM::update($this, $refresh);
	}
	
	function delete() {
		return ORM::delete($this);
	}
	
	static function deleteAll($_ = null) {
		return ORM::deleteAll(get_called_class(), func_get_args());
	}

	static function find($_ = null) {
		return ORM::find(get_called_class(), func_get_args());
	}
	
	static function findOne($_ = null) {
		return ORM::findOne(get_called_class(), func_get_args());
	}
	
	static function exists($pk) {
		if (!is_array($pk))
			$pk = func_get_args();
		return ORM::exists(get_called_class(), $pk);
	}
	
	static function count($_ = null) {
		return ORM::count(get_called_class(), func_get_args());
	}

	static function aggregate(Aggregate $aggregate, $_ = null) {
		return ORM::aggregate(get_called_class(), $aggregate, array_slice(func_get_args(), 1));
	}
	
	static function pageCount($pageSize, $_ = null) {
		return ORM::pageCount(get_called_class(), $pageSize, array_slice(func_get_args(), 1));
	}

	static function property($property, $_ = null) {
		return ORM::property(get_called_class(), $property, array_slice(func_get_args(), 1));
	}
	
	static function distinct($property, $_ = null) {
		return ORM::distinct(get_called_class(), $property, array_slice(func_get_args(), 1));
	}
	
	function bring($property, $_ = null) {
		return ORM::bring($this, $property, array_slice(func_get_args(), 1));
	}

	static function ormcriteria() {
		return ORM::getORMCriteria(get_called_class());
	}
	
	static function criteria() {
		return ORM::getCriteria(get_called_class());
	}
	
	static function getDataSource() {
		return ORM::getDataSource(get_called_class());
	}

	static function getDescriptor() {
		return Descriptor::getInstance(get_called_class());
	}
	
	function getPKValues($raw = false) {
		return ORM::getPKValues($this, $raw);
	}
	
	function getSinglePKValue($raw = false) {
		return ORM::getSinglePKValue($this, $raw);
	}	
}
