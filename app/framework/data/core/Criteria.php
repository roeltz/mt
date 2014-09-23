<?php

namespace data\core;
use data\core\criterion\Order;
use data\core\criterion\Limit;
use data\core\criterion\Fields;
use data\core\criterion\Distinct;
use data\core\exception\CriteriaException;

class Criteria {
	
	public $dataSource;
	public $collection;
	public $fields;
	public $distinct;
	public $criterions = array();
	public $expressions = array();
	public $orders = array();
	public $limit;
	
	function __construct(DataSource $dataSource, Collection $collection = null) {
		$this->dataSource = $dataSource;
		$this->collection = $collection;
	}
	
	function __clone() {
		$this->expressions = unserialize(serialize($this->expressions));
		$this->criterions = unserialize(serialize($this->criterions));
	}
	
	function from($collection) {
		return $this->add($collection instanceof Collection ? $collection : new Collection($collection));
	}
	
	function fields(array $fields) {
		return $this->add(new Fields($fields));
	}
	
	function distinct($field) {
		return $this->add(new Distinct($field));
	}
	
	function add(Criterion $criterion) {
		if ($criterion instanceof Expression)
			$this->expressions[] = $criterion;
		elseif ($criterion instanceof Order)
			$this->orders[] = $criterion;
		elseif ($criterion instanceof Limit)
			$this->limit = $criterion;
		elseif ($criterion instanceof Collection)
			$this->collection = $criterion;
		elseif ($criterion instanceof Fields) {
			$this->fields = $criterion->fields;
			$this->distinct = false;
		} elseif ($criterion instanceof Distinct) {
			$this->fields = array($criterion->field);
			$this->distinct = true;
		} else
			$this->criterions[] = $criterion;
		return $this;
	}
	
	function query() {
		return $this->dataSource->find($this, $this->fields);
	}
	
	function querySingle() {
		return $this->dataSource->find($this, $this->fields, DataSource::QUERY_RETURN_SINGLE);
	}
	
	function querySeries() {
		return $this->dataSource->find($this, $this->fields, DataSource::QUERY_RETURN_SERIES);
	}
	
	function queryValue() {
		return $this->dataSource->find($this, $this->fields, DataSource::QUERY_RETURN_VALUE);
	}
	
	function count() {
		return $this->dataSource->count($this);
	}
	
	function pageCount($pageSize) {
		return floor($this->count($args) / $pageSize) + 1;
	}
	
	function aggregate(Aggregate $aggregate) {
		return $this->dataSource->aggregate($aggregate, $this);
	}
	
	private function check() {
		if (!$this->collection)
			throw new CriteriaException(__("A collection has not been set for the criteria"));
	}
}
