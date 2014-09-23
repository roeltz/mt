<?php

namespace data\orm;
use data\core\Criteria;
use data\core\CriteriaDecorator;
use data\core\Criterion;
use data\core\Aggregate;
use data\core\Restrict;
use data\core\criterion\Order;
use data\orm\criterion\Bring;
use data\orm\criterion\Index;
use data\orm\criterion\Group;

class ORMCriteria extends CriteriaDecorator {
	
	public $descriptor;
	
	function __construct(Criteria $criteria, Descriptor $descriptor) {
		
		parent::__construct($criteria);
		$this->descriptor = $descriptor;
		
		if ($this->descriptor->isSubclass)
			$this->add(Restrict::eq(
						$this->descriptor->getDiscriminatorProperty(),
						$this->descriptor->getDiscriminatorValue()));
	}
	
	function query() {
		$this->checkOrderBydefault();
		$this->checkAliases();
		$result = $this->criteria->query();
		$objects = ORM::processResult($this->descriptor->class, $result);
		return $this->processAll($objects, $result);
	}
	
	function querySingle() {
		$this->checkOrderBydefault();
		$this->checkAliases();
		$record = $this->criteria->querySingle();
		$class = ORM::resolveClass($this->descriptor->class, $record);
		$object = new $class;
		ORM::cast($object, $record);
		$this->processSingle($object);
		return $object;
	}
	
	function querySeries() {
		$this->checkAliases();
		return $this->criteria->querySeries();
	}
	
	function queryValue() {
		$this->checkAliases();
		return $this->criteria->queryValue();		
	}
	
	function count() {
		$this->checkAliases();
		return $this->criteria->count();
	}
	
	function pageCount($pageSize) {
		$this->checkAliases();
		return $this->criteria->pageCount($pageSize);		
	}
	
	function aggregate(Aggregate $aggregate) {
		$extra = array(&$aggregate);
		$this->checkAliases($extra);
		return $this->criteria->aggregate($aggregate);
	}
	
	protected function processSingle(&$object) {
		foreach($this->criterions as $criterion) {
			if ($criterion instanceof Bring) {
				foreach($criterion->properties as $property)
					ORM::bring($object, $property);
			}
		}
	}
	
	protected function processAll(array &$objects, array &$records) {
		foreach($objects as &$object)
			$this->processSingle($object);
		
		foreach($this->criterions as $criterion) {
			if ($criterion instanceof Index) {
				// TODO Throw exception on compound fk properties
				$indexed = array();
				foreach($objects as $i=>$object) {
					if ($criterion->byPK) {
						$pk = ORM::getPKValues($object);
						$key = reset($pk);
					} else
						$key = @$records[$i][$this->descriptor->getPropertyAlias($criterion->property)];
					if ($key instanceof DateTime) $key->format("Y-m-d H:i:s");
					$indexed[(string) $key] = $object;
				}
				return $indexed;
			} elseif($criterion instanceof Group) {
				$grouped = array();
				foreach($objects as &$object) {
					$key = @$records[$i][$this->descriptor->getPropertyAlias($criterion->property)];
					if ($key instanceof DateTime) $key->format("Y-m-d H:i:s");
					$grouped[(string) $key][] = $i;
				}
				return $grouped;
			}
		}
		return $objects;
	}
	
	
	protected function checkOrderByDefault() {
		if (count($this->orders)) return;
		
		foreach($this->descriptor->orderByDefault as $property=>$orderByDefault)
			$this->add(new Order($property, $orderByDefault->value));
	}
	
	protected function checkAliases(array &$optional = null) {
		ORM::translateProperties($this->descriptor, $this, $optional);
		return $this;
	}
}
