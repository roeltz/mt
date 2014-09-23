<?php

namespace data\orm;
use ReflectionClass;
use foo\util\EventSource;
use data\core\Aggregate;
use data\core\Collection;
use data\core\Connections;
use data\core\Criteria;
use data\core\Expression;
use data\core\Restrict as R;
use data\core\DataSource;
use data\core\SQLDataSource;
use data\core\criterion\Limit;
use data\core\criterion\Order;
use data\core\exception\DuplicateEntryException;
use data\core\expression\JunctionExpression;
use data\core\expression\ComparissionExpression;
use data\core\expression\ListExpression;
use data\core\expression\RangeExpression;
use data\core\expression\SqlExpression;
use data\core\support\DeepFields;
use data\orm\cache\InstanceCache;
use data\orm\cache\RecordCache;
use data\orm\exception\InstanceNotFoundException;
use data\orm\exception\InconsistencyException;

abstract class ORM {
	
	protected static $events;
	
	static function events() {
		if (!self::$events)
			self::$events = new EventSource(null);
		return self::$events;
	}
	
	static function retrieve($instance, array $pk) {
		$class = get_class($instance);
		$descriptor = Descriptor::getInstance($class);
		
		if (!is_array($pk))
			$pk	= array_slice(func_get_args(), 1);
			
		if (!is_assoc($pk))
			$pk = self::buildPK($class, $pk);

		$criteria = self::getCriteria($class)->add(R::eqAll($pk));
		$criteria = self::translateProperties($descriptor, $criteria);
		$record = $criteria->querySingle();
		
		if (is_array($record)) {
			self::events()->triggerContext("beforeCast", $instance, $record);
			self::events()->triggerContext("beforeCast:$class", $instance, $record);
			self::cast($instance, $record);
			InstanceCache::set($instance, $pk);
			self::events()->triggerContext("afterCast", $instance);
			self::events()->triggerContext("afterCast:$class", $instance);
			return $instance;
		} else {
			throw new InstanceNotFoundException($descriptor->class, $pk);
		}
	}
	
	static function exists($class, array $pk) {
		if (!is_assoc($pk))
			$pk = self::buildPK($class, $pk);
		
		return self::getCriteria($class)->add(R::eqAll($pk))->count() > 0;
	}

	static function save($instance, $refresh = true) {
		$class = get_class($instance);
		$descriptor = Descriptor::getInstance($class);

		self::events()->triggerContext("beforeSave", $instance);
		self::events()->triggerContext("beforeSave:$class", $instance);
		
		try {
			$values = self::getPersistedValues($instance);
			$sequence = $descriptor->getPKSequence();
			$generated = self::getDataSource($class)
							->save(
								$descriptor->collection,
								$values,
								$sequence,
								$sequence ? null : $descriptor->generated
							);
			
			if ($descriptor->generated) {
				$instance->{$descriptor->generated} = $generated;
			}
			
			if ($refresh) {
				if ($descriptor->generated) {
					self::retrieve($instance, array($descriptor->generated=>$generated));
				} else {
				self::retrieve($instance, self::getPKValues($instance));
				}
			}
		} catch(DuplicateEntryException $e) {
			throw new InconsistencyException(__("Object of class {class} already exists: {message}", array('class'=>$descriptor->class, 'message'=>$e->getMessage())), $e->getCode(), $e);
		}
		
		self::events()->triggerContext("afterSave", $instance);
		self::events()->triggerContext("afterSave:$class", $instance);
	}
	
	static function update($instance, $refresh = true) {
		$class = get_class($instance);
		$pk = self::getPKValues($instance);
		$values = self::getPersistedValues($instance, true);
		
		self::events()->triggerContext("beforeUpdate", $instance);
		self::events()->triggerContext("beforeUpdate:$class", $instance);
		
		if (count($values)) {
			self::getDataSource($class)
					->update(
						self::getCriteria($class)->add(R::eqAll($pk)),
						$values
					);

			if ($refresh) {
				self::retrieve($instance, $pk);
			}
		}
		
		self::events()->triggerContext("afterUpdate", $instance);
		self::events()->triggerContext("afterUpdate:$class", $instance);
	}
	
	static function delete($instance) {
		$class = get_class($instance);
		$descriptor = Descriptor::getInstance($class);
		$pk = self::getPKValues($instance);
		
		self::events()->triggerContext("beforeDelete", $instance);
		self::events()->triggerContext("beforeDelete:$class", $instance);
		
		if ($descriptor->hasCascadeDelete())
			foreach($descriptor->getCascadeDeleteProperties() as $property)
				foreach((array) self::bring($instance, $property) as $object)
					$object->delete();
		
		self::getDataSource($class)
				->delete(self::getCriteria($class)->add(R::eqAll($pk)));
		InstanceCache::remove($class, $pk);
		RecordCache::remove($instance);
		
		self::events()->triggerContext("afterDelete", $instance);
		self::events()->triggerContext("afterDelete:$class", $instance);
	}
	
	static function deleteAll($class, $_ = null) {
		$args = \array_flatten(array_slice(func_get_args(), 1));
		$criteria = self::getORMCriteria($class);
		foreach($args as $a)
			$criteria->add($a);
		self::getDataSource($class)
				->delete($criteria->checkAliases());
	}
	
	static function find($class, $_ = null) {
		$args = \array_flatten(array_slice(func_get_args(), 1));
		$criteria = self::getORMCriteria($class);
		foreach($args as $a)
			$criteria->add($a);
		return $criteria->query();
	}

	static function findOne($class, $_ = null) {
		$args = array(array_slice(func_get_args(), 1), Limit::single());
		$result = self::find($class, $args);
		return @$result[0];		
	}

	static function count($class, $_ = null) {
		$args = \array_flatten(array_slice(func_get_args(), 1));
		$criteria = self::getORMCriteria($class);
		foreach($args as $a)
			$criteria->add($a);
		return $criteria->count();
	}

	static function aggregate($class, Aggregate $aggregate, $_ = null) {
		$args = \array_flatten(array_slice(func_get_args(), 2));
		$criteria = self::getORMCriteria($class);
		foreach($args as $a)
			$criteria->add($a);
		return $criteria->aggregate($aggregate);
	}

	static function pageCount($class, $pageSize, $_ = null) {
		$args = \array_flatten(array_slice(func_get_args(), 2));
		$criteria = self::getORMCriteria($class);
		foreach($args as $a)
			$criteria->add($a);
		return $criteria->pageCount($pageSize);		
	}

	static function property($class, $property, $_ = null) {
		if ($unprocessed = preg_match('/^!/', $property))
			$property = ltrim($property, '!');
		$args = \array_flatten(array_slice(func_get_args(), 2));
		$criteria = self::getCriteria($class);
		$criteria->fields(array($property));
		foreach($args as $a)
			$criteria->add($a);
		$criteria = self::translateProperties(Descriptor::getInstance($class), $criteria);
		$result = $criteria->query();
		if ($unprocessed) {
			return $result;
		} else {
			return self::processPartialResult($class, $result, array($property));
		}
	}

	static function distinct($class, $property, $_ = null) {
		$args = \array_flatten(array_slice(func_get_args(), 2));
		$criteria = self::getCriteria($class);
		$criteria->distinct($property);
		foreach($args as $a)
			$criteria->add($a);
		$criteria = self::translateProperties(Descriptor::getInstance($class), $criteria);
		$result = $criteria->query();
		return self::processPartialResult($class, $result, array($property));
	}
	
	static function sql($class, $sql, array $parameters = null) {
		if (($dataSource = self::getDataSource($class)) instanceof SQLDataSource) {
			$result = $dataSource->query($sql, $parameters, DataSource::QUERY_RETURN_ALL);
			return self::processResult($class, $result);
		} else {
			throw new exception\ORMException(__("{class} class is not bound to a SQLDataSource. SQL queries on this class are not possible.", compact('class')));
		}
	}
	
	static function getCriteria($class) {
		$descriptor = Descriptor::getInstance($class);
		return self::getDataSource($class)->criteria()
					->from(clone $descriptor->collection)
					->fields($descriptor->getAliasedPersistedProperties());
	}
	
	static function getORMCriteria($class) {
		$descriptor = Descriptor::getInstance($class);
		$criteria = new ORMCriteria(self::getCriteria($class), $descriptor);
		$criteria->fields($descriptor->getAliasedPersistedProperties());
		return $criteria;
	}
	
	static function getDataSource($class) {
		return Connections::get(Descriptor::getInstance($class)->dataSource);
	}
	
	static function isPersistible($instance) {
		try {
			if (is_object($instance)) {
				$class = get_class($instance);
				$reflectionClass = new ReflectionClass($class);
				if ($reflectionClass->isUserDefined()) {
					Descriptor::getInstance($class);
					return true;
				}
			}
		} catch(DescriptorException $e) {}
		return false;	
	}
	
	static function buildPK($class, array $args) {
		$descriptor = Descriptor::getInstance($class);
		$pk = array();
		if (is_array($rawPK = $args[0])) {
			foreach($rawPK as $property=>$value) {
				if (($value instanceof DateTime) && ($datetime = @$descriptor->datetime[$property]))
					$value = self::processDate($value, $datetime);

				$pk[$descriptor->getPropertyAlias($property)] = $value;
			}
		} else {
			foreach(array_keys($descriptor->pk) as $i=>$property) {
				if (($args[$i] instanceof DateTime) && ($datetime = @$descriptor->datetime[$property]))
					$args[$i] = self::processDate($args[$i], $datetime);
				
				$pk[$descriptor->getPropertyAlias($property)] = $args[$i];
			}
		}
		return $pk;		
	}
	
	static function getPKValues($instance) {
		$descriptor = Descriptor::getInstance(get_class($instance));
		$pk = array();

		foreach($descriptor->pk as $property=>$id)
			foreach(self::getPropertyValue($instance, $property) as $k=>$v)
				$pk[$k] = $v;

		return $pk;
	}
	
	static function getPropertyValue($instance, $property) {
		$descriptor = Descriptor::getInstance(get_class($instance));

		if ($descriptor->isRelationToOne($property) && !$descriptor->isEmbeddedProperty($property) && self::isPersistible($instance->$property)) {
			$fk = array();
			$values = array_values(self::getPKValues($instance->$property));
			foreach((array) $descriptor->one[$property]->fk as $i=>$k)
				$fk[$k] = $values[$i];
			return $fk;
		} elseif ($descriptor->isCompoundFK($property) && is_null($instance->$property)) {
			return array_combine(
				$c = $descriptor->compound[$property],
				array_fill(0, count($c), null)
			);
		} else {
			return array($descriptor->alias[$property]=>$instance->$property);
		}
	}
	
	static function getPersistedValues($instance, $isUpdate = false) {
		$descriptor = Descriptor::getInstance(get_class($instance));
		$values = array();
		
		foreach($descriptor->persisted as $property) {
			$propertyValues = null;
			$alias = $descriptor->alias[$property];
			$one = @$descriptor->one[$property];
			$many = @$descriptor->many[$property];
			$embedded = @$descriptor->embedded[$property];
			$transform = @$descriptor->transformed[$property];
			
			if ($one || ($many && $embedded) || !$many) {
				
				if ($one
					&& $descriptor->isCompoundFK($property)
					&& $descriptor->isNotNullProperty($property)
					&& is_null($instance->$property))
					throw new InconsistencyException(__("Not-null property {class}::{property} is null", array('class'=>$descriptor->class, 'property'=>$property)));
									
				$propertyValues = self::getPropertyValue($instance, $property);
				$propertySingleValue = count($propertyValues) == 1 ? reset($propertyValues) : null;
				
				if ($one && $embedded) {
					$propertyValues = array($alias=>self::getPersistedValues($propertySingleValue));
				} elseif ($many && $embedded) {
					$embeddedValues = array();
					foreach((array) $propertySingleValue as $v) {
						if ($embedded->refOnly) {
							$embeddedValues[] = self::getPKValues($v);
						} elseif (self::isPersistible($v)) {
							$embeddedValues[] = self::getPersistedValues($v);
						} else {
							$embeddedValues[] = $v;
						}
					}
					$propertyValues = array($alias=>$embeddedValues);
				}
				
				if (count($propertyValues) == 1) {

					$pk = @$descriptor->pk[$property];
					$nullable = !$descriptor->isNotNullProperty($property);
					
					if (is_null($propertySingleValue) && (!$nullable || $pk && (!$pk->auto || $isUpdate)))
						throw new InconsistencyException("Not-null property {$descriptor->class}::$property is null");
					elseif (is_null($propertySingleValue) && $pk && $pk->auto && !$isUpdate)
						continue;
					
					if (($propertySingleValue instanceof DateTime) && ($datetime = @$descriptor->datetime[$property])) {
						$propertySingleValue = self::processDate($value, $datetime);
					}

					if ($transform) {
						$propertySingleValue = $transform->apply($propertySingleValue, $transform->params, $isUpdate);
					}

					$values[$alias] = $propertySingleValue;
					
				} else {
					foreach($propertyValues as $k=>$v)
						$values[$k] = $v;
				}
			}
		}

		$record = RecordCache::get($instance);
		if ($isUpdate) {
			foreach($values as $k=>$v) {
				$vr = @$record[$k];
				if ((!is_object($v) && ($v === $vr)) || (($v instanceof DateTime) && ($vr instanceof DateTime) && ($v->getTimestamp() == $vr->getTimestamp())))
					unset($values[$k]);
			}
		} elseif (!$record) {
			RecordCache::set($instance, $values);
		}

		return $values;
	}
	
	static function getCastInformation($class, array $record, array $properties = null, $partial = false) {
		$descriptor = Descriptor::getInstance($class);
		$values = array();
		$ownRecord = array();
		$toBring = array();
		$toUnset = array();
		
		if (is_null($properties))
			$properties = $descriptor->persisted;
		
		foreach($properties as $property) {
			$set = true;
			$value = @$record[$alias = $descriptor->getPropertyAlias($property)];
			$one = @$descriptor->one[$property];
			$many = @$descriptor->many[$property];
			$embedded = @$descriptor->embedded[$property];
			$compound = @$descriptor->compound[$property];
			$transform = @$descriptor->transformed[$property];
			
			if ($transform)
				$value = $transform->undo($value);
			
			if (!$partial && $one || $many) {
				$set = false;
				$unset[] = $property;
				if (($one && !$one->lazy) || ($many && !$many->lazy) || ($embedded && !$embedded->refOnly))
					$toBring[] = $property;
			}
			
			if ($compound) {
				foreach($compound as $fk)
					$ownRecord[$fk] = $record[$fk];
			} else {
				$ownRecord[$alias] = $transform ? $record[$alias] : $value;
			}
			
			if ($set)
				$values[$property] = $value;
			else
				$toUnset[] = $property;
		}
		
		foreach($descriptor->eager as $property)
			if (!in_array($property, $toBring)) {
				$toUnset[] = $property;
				$toBring[] = $property;
			}
		
		return array($values, $ownRecord, $toBring, $toUnset);
	}
	
	static function cast($instance, array $record) {
		$class = get_class($instance);
		$descriptor = Descriptor::getInstance($class);
		
		list($values, $ownRecord, $toBring, $toUnset) = self::getCastInformation($class, $record);
		
		foreach($values as $property=>$value)
			$instance->$property = $value;
		
		foreach($toUnset as $property)
			unset($instance->$property);
		
		foreach($descriptor->getBroughtProperties() as $property)
			unset($instance->$property);
		
		if (!$descriptor->embeddedClass)
			 RecordCache::set($instance, $record);

		foreach($toBring as $property)
			self::bring($instance, $property);
		
		InstanceCache::set($instance, self::getPKValues($instance));
	}
	
	static function bring($instance, $property, $_ = null) {
		if (count($path = explode(".", $property)) > 1) {
			$result = self::bringProperty($instance, array_shift($path), array_slice(func_get_args(), 2));

			if (is_array($result)) {
				foreach($result as &$object)
					self::bring($object, join(".", $path));
			} elseif (!is_null($result)) {
				self::bring($result, join(".", $path));
			}
	
			return $result;
		} else {
			return self::bringProperty($instance, $property, array_slice(func_get_args(), 2));
		}
	}
	
	static function bringProperty($instance, $property, $_ = null) {
		$descriptor = Descriptor::getInstance(get_class($instance));
		$embedded = @$descriptor->embedded[$property];
		$record = RecordCache::get($instance);

		if ($one = @$descriptor->one[$property]) {
			$class = $one->class;

			if ($embedded && ($record = @$record[$one->fk])) {
				$object = new $class;
				self::cast($object, $record);
				return $instance->$property = $object;
			} elseif (!$embedded) {
				$pk = array();
				$classPK = Descriptor::getInstance($class)->getAliasedPK();
				foreach((array) $one->fk as $i=>$fkk)
					$pk[$classPK[$i]] = $record[$fkk];
				return $instance->$property = self::getInstanceFromCache($one->class, $pk);
			}
			
		} elseif ($many = @$descriptor->many[$property]) {
			$class = $many->class;
			if ($embedded) {
				$values = (array) $record[$descriptor->getPropertyAlias($property)];
				if ($embedded->refOnly) {
					return $instance->$property = array_map(
						function($pk) use($class){
							$object = new $class;
							ORM::retrieve($object, $pk);
							return $object;
						},
						$values);
				} else {
					return $instance->$property = array_map(
						function($r) use($class){
							$object = new $class;
							ORM::cast($object, $r);
							return $object;
						},
						$values); 
				}
			} else {
				$criterions = array_slice(func_get_args(), 2);
				if ($many->order)
					foreach($many->order as $orderProperty=>$type)
						$criterions[] = new Order($orderProperty, $type);
				$fk = array_combine((array) $many->fk, array_values(self::getPKValues($instance)));
				array_unshift($criterions, R::eqAll($fk));
				return $instance->$property = self::find($class, $criterions);
			}
		} elseif ($discriminatedOnes = @$descriptor->discriminatedOnes[$property]) {
			foreach($discriminatedOnes as $dOne) {
				$alias = $descriptor->getPropertyAlias($dOne->property);
				if ($record[$alias] === $dOne->value) {
					$class = $dOne->class;
					$pk = array($dOne->fk=>reset(self::getPropertyValue($instance, $property)));
					return $instance->$property = self::getInstanceFromCache($class, $pk);
				}
			}
		}
	}
	
	static function getInstanceFromCache($class, array $pk) {
		if ($object = InstanceCache::get($class, $pk)) {
			return $object;
		} else {
			try {
				$object = new $class;
				return self::retrieve($object, $pk);
			} catch(InstanceNotFoundException $e) {}
		}
	}
	
	static function translateProperties(Descriptor $descriptor, Criteria $criteria, array &$optional = null) {
		$solver = new RelationalSolver($descriptor);
		return $solver->solve($criteria, $optional);
	}

	static function resolveClass($class, array &$record) {
		$descriptor = Descriptor::getInstance($class);
		if ($descriptor->discriminate)
			foreach($descriptor->discriminate->classes as $subclass) {
				$subdescriptor = Descriptor::getInstance($subclass);
				if (@$record[$subdescriptor->getAliasedDiscriminatorProperty()] == $subdescriptor->discriminate->value)
					return $subclass;
			}
		return $class;
	}

	static function processDate(DateTime $value, DateTimeAnnotation $annotation) {
		if ($annotation->ignoreTimezone)
			$value = new DateTime($value->format("Y-m-d H:i:s"));
		if ($annotation->dateOnly)
			$value = new DateTime($value->format("Y-m-d 00:00:00"));
		elseif ($annotation->timeOnly)
			$value = new DateTime($value->format("0000-00-00 H:i:s"));
		return $value;
	}

	static function processResult($class, array $result) {
		$objects = array();
		foreach($result as $record) {
			$resolvedClass = self::resolveClass($class, $record);
			$object = new $resolvedClass;
			ORM::cast($object, $record);
			$objects[] = $object;
		}
		return $objects;
	}
	
	static function processPartialResult($class, array $result, array $properties) {
		$values = array();
		foreach($result as $record) {
			list($v) = self::getCastInformation($class, $record, $properties, true);
			$values[] = reset($v);
		}
		return $values;
	}
}
