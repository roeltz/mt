<?php

namespace data\orm;
use data\core\Aggregate;
use data\core\Criteria;
use data\core\criterion\Order;
use data\core\Expression;
use data\core\expression\ComparissionExpression;
use data\core\expression\JunctionExpression;
use data\core\expression\ListExpression;
use data\core\expression\RangeExpression;
use data\core\expression\SqlExpression;
use data\core\Field;
use data\core\Restrict;
use data\core\support;

class RelationalSolver {
	protected $descriptor;
	protected $collectionCache = array();
	
	function __construct(Descriptor $descriptor) {
		$this->descriptor = $descriptor;
	}
	
	function solve(Criteria $criteria, &$optional = null) {
		if ($criteria->dataSource instanceof support\DeepFields)
			return $criteria;
		$self = $this;
		$descriptor = $this->descriptor;
		$criteria = clone $criteria;
		$criteria->collection = $this->getCollection($descriptor);
		$expressions = array($criteria->expressions, $criteria->criterions, $optional);
		
		\object_walk_recursive(function(&$c, $K, $source) use($self, $descriptor, &$criteria){

			if (($c instanceof Expression && !($c instanceof JunctionExpression || $c instanceof SqlExpression))
				|| $c instanceof Order
				|| $c instanceof Aggregate) {
					
				list($field, $lastDescriptor, $joinFieldPairs) = $self->getElementsForPath($c->field, $descriptor);
				
				$originalField = $c->field;
				$c->field = $field;

				if ($c instanceof ComparissionExpression) {
					
					if ($c->valueIsField) {
						list($otherField, $otherLastDescriptor, $otherJoinFieldPairs) = $self->getElementsForPath($c->value, $descriptor);
						$c->value = $otherField;
						
						foreach($otherJoinFieldPairs as $joinFieldPair) {
							foreach($criteria->collection->joins as $join)
								if ($join->collection == $joinFieldPair[1]->collection)
									continue 2;
							$criteria->collection->join($joinFieldPair[1]->collection, Restrict::eqf($joinFieldPair[0], $joinFieldPair[1]));
						}						
					} elseif (ORM::isPersistible($c->value)) {
						$pkValues = ORM::getPKValues($c->value);
						if (count($pkValues) == 1) {
							$c->value = reset($pkValues);
						} else {
							$fk = array();
							$pkValues = array_values($pkValues);
							foreach((array) $lastDescriptor->one[$field->name]->fk as $i=>$k)
								$fk[$k] = $pkValues[$i];
							$c = Restrict::eqAll($fk);
						}
					}
				} elseif ($c instanceof ListExpression) {
					$values = array();
					foreach($c->values as $v) {
						if (ORM::isPersistible($v)) {
							$pkval = ORM::getPKValues($v);
							$v = reset($pkval);
						}
						$values[] = $v;
					}
					$c = new ListExpression($field, $c->operator, $values);
				} else {
					$c->field = $field;
				}
				
				foreach($joinFieldPairs as $joinFieldPair) {
					foreach($criteria->collection->joins as $join)
						if ($join->collection == $joinFieldPair[1]->collection)
							continue 2;
					$criteria->collection->join($joinFieldPair[1]->collection, Restrict::eqf($joinFieldPair[0], $joinFieldPair[1]));
				}
			}

			return $c;
			
		}, $expressions);
		
		$criteria->expressions = $expressions[0];
		$criteria->criterions = $expressions[1];
		$optional = $expressions[2];
		
		return $criteria;
	}
	
	function getCollection(Descriptor $descriptor, array $previous = array()) {
		$key = join(">>", array_merge(array_map(function($d){ return $d['descriptor']->class; }, $previous), array($descriptor->class)));
		if (!isset($this->collectionCache[$key])) {
			$this->collectionCache[$key] = clone $descriptor->collection;
			$this->collectionCache[$key]->alias = "c" .  count($this->collectionCache);
		}
		return $this->collectionCache[$key];
	}

	function getElementsForProperty($property, Descriptor $descriptor, array $previous = array()) {
		$field = new Field($descriptor->getPropertyAlias($property), $this->getCollection($descriptor, $previous));
		return array($field, $descriptor);
	}
	
	function getElementsForPath($path, Descriptor $descriptor, array $previous = array(), array $joins = array()) {
		$components = explode(".", $path);
		if (count($components) == 1) {
			list($field) = $this->getElementsForProperty($path, $descriptor, $previous);
			if ($previous) {
				$prev = end($previous);
				$pk = $descriptor->getAliasedPK();
				$joins[] = array($prev['field'], new Field(reset($pk), $this->getCollection($descriptor, $previous)));
			}
			return array($field, $descriptor, $joins);
		} else {
			list($field) = $this->getElementsForProperty($components[0], $descriptor, $previous);
			if (isset($descriptor->one[$components[0]])) {
				$nextDescriptor = Descriptor::getInstance($descriptor->one[$components[0]]->class);
			} else {
				$nextDescriptor = $descriptor;
			}
			if ($previous) {
				$prev = end($previous);
				$joins[] = array($prev['field'], $field);
			} else {
				$pk = $nextDescriptor->getAliasedPK();
				$joins[] = array($field, new Field(reset($pk), $this->getCollection($nextDescriptor, array(array('field'=>$field, 'descriptor'=>$descriptor)))));
			}
			$previous[] = array('field'=>$field, 'descriptor'=>$descriptor);
			return $this->getElementsForPath(join(".", array_slice($components, 1)), $nextDescriptor, $previous, $joins);
		}
	}
}
