<?php

namespace data\core;
use IteratorAggregate;
use ArrayIterator;
use data\core\criterion\Order;
use data\core\criterion\Limit;
use data\core\criterion\Fields;
use data\core\criterion\Distinct;
use data\core\exception\CriteriaException;

abstract class CriteriaDecorator extends Criteria implements IteratorAggregate {

	protected static $redirected = array(
		"dataSource", "collection", "fields", "distinct",
		"criterions", "expressions", "orders", "limit"); 

	protected $criteria;	
	
	function __construct(Criteria $criteria) {
		$this->criteria = $criteria;
		foreach(self::$redirected as $k)
			unset($this->$k);
	}
	
	final function __get($property) {
		if (in_array($property, self::$redirected))
			return $this->criteria->$property;
	}

	final function __set($property, $value) {
		if (in_array($property, self::$redirected))
			return $this->criteria->$property = $value;
	}
	
	function getIterator() {
		$iterator = new ArrayIterator((array) $this);
		foreach(self::$redirected as $property)
			$iterator->append($this->$property);
		return $iterator;
	}
	
	function from($collection) {
		$this->criteria->from($collection);
		return $this;
	}
	
	function fields(array $fields) {
		$this->criteria->fields($fields);
		return $this;
	}
	
	function distinct($field) {
		return $this->criteria->distinct($field);
	}
	
	function add(Criterion $criterion) {
		$this->criteria->add($criterion);
		return $this;
	}
	
	function query() {
		return $this->criteria->query();
	}
	
	function querySingle() {
		return $this->criteria->querySingle();
	}
	
	function querySeries() {
		return $this->criteria->querySeries();
	}
	
	function queryValue() {
		return $this->criteria->queryValue();
	}
	
	function count() {
		return $this->criteria->count();
	}
	
	function pageCount($pageSize) {
		return $this->criteria->pageCount($pageSize);
	}
	
	function aggregate(Aggregate $aggregate) {
		return $this->criteria->aggregate($aggregate);
	}	
}
