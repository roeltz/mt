<?php

namespace data\orm;
use ReflectionAnnotatedClass;
use ReflectionAnnotatedProperty;
use data\core\Collection;
use data\orm\exception\DescriptorException;
use foo\util\cache\Cache;

class Descriptor {
	
	public $class;
	public $collection;
	public $embeddedClass = false;
	public $dataSource = "default";

	public $discriminate;
	public $isSubclass = false;
	
	public $pk = array();
	public $generated;
	public $one = array();
	public $many = array();
	public $discriminatedOnes = array();
	public $compound = array();
	public $eager = array();
	
	public $persisted = array();
	public $notNull = array();
	public $transformed = array();
	public $embedded = array();
	public $cascadeDelete = array();
	public $datetime = array();
	
	public $alias = array();
	
	public $orderByDefault = array();
	
	static function getInstance($class) {
		if (!($descriptor = Cache::get("memory")->get("data\\orm\\Descriptor", $class)))
			Cache::get("memory")->set("data\\orm\\Descriptor", $class, $descriptor = new Descriptor($class));
		return $descriptor;
	}
	
	private function __construct($class) {
		$annotatedClass = new ReflectionAnnotatedClass($this->class = $class);
		$this->surveyEntity($annotatedClass);
		
		foreach ($annotatedClass->getProperties() as $property)
			if (!$property->hasAnnotation('data\\orm\\annotations\\Transient'))
				$this->surveyProperty($property);
			
		$this->surveySubclasses($annotatedClass);
		
		$this->persisted = array_unique($this->persisted);
	}
	
	function surveyEntity(ReflectionAnnotatedClass $class) {

		if ($embedded = $class->getAnnotation("data\\orm\\annotations\\Embedded"))
			$this->embeddedClass = true;
		else {
			$collectionClass = $class;
			while($collectionClass) {
				if ($collection = $collectionClass->getAnnotation("data\\orm\\annotations\\Collection")) {
					$this->collection = new Collection($collection->value);
					break;
				}
				$collectionClass = $collectionClass->getParentClass();
			}
			
			if (!$this->collection && !$class->isAbstract())
				throw new DescriptorException(__("Class {class} lacks @orm\\Collection annotation", array('class'=>$this->class)));
		}
		
		if ($dataSource = $class->getAnnotation("data\\orm\\annotations\\DataSource"))
			$this->dataSource = $dataSource->value;
	}
	
	function surveyProperty(ReflectionAnnotatedProperty $property) {
		$name = $property->getName();
		$alias = $property->getAnnotation("data\\orm\\annotations\\Alias");
		$id = $property->getAnnotation("data\\orm\\annotations\\Id");
		$one = $property->getAnnotation("data\\orm\\annotations\\One");
		$discriminatedOnes = $property->getAllAnnotations("data\\orm\\annotations\\DiscriminatedOne");
		$many = $property->getAnnotation("data\\orm\\annotations\\Many");
		$notNull = $property->getAnnotation("data\\orm\\annotations\\NotNull");
		$embedded = $property->getAnnotation("data\\orm\\annotations\\Embedded");
		$transform = $property->getAnnotation("data\\orm\\annotations\\Transform");
		$orderByDefault = $property->getAnnotation("data\\orm\\annotations\\OrderByDefault");
		$datetime = $property->getAnnotation("data\\orm\\annotations\\DateTime");
		$cascadeDelete = $property->getAnnotation("data\\orm\\annotations\\CascadeDelete");
		$isCompound = false;
		
		if ($id) {
			$this->pk[$name] = $id;
			if ($id->auto && !$one)
				$this->generated = $name;
		}

		if ($alias)
			$this->alias[$name] = $alias->value;
		else
			$this->alias[$name] = $name;
		
		if ($one) {
			$this->one[$name] = $one;
			if ($isCompound = (count($one->fk) > 1))
				$this->compound[$name] = $one->fk;
			elseif (!$one->fk)
				$one->fk = $name;
			if (!$isCompound)
				$this->alias[$name] = $one->fk;
		} elseif ($many) {
			$this->many[$name] = $many;
			if (count($many->fk) > 1)
				$this->compound[$name] = $many->fk;
		} elseif ($discriminatedOnes) {
			$this->discriminatedOnes[$name] = $discriminatedOnes;
		}
		
		if (($one && !$one->lazy) || ($many && !$many->lazy))
			$this->eager[] = $name;
		
		if ($id || ($one && !$isCompound) || (!$one && !$many && !$discriminatedOnes) || $embedded)
			$this->persisted[] = $name;
		
		if ($notNull)
			$this->notNull[] = $name;
		
		if ($embedded)
			$this->embedded[$name] = $embedded;
		
		if ($transform)
			$this->transformed[$name] = $transform;
		
		if ($orderByDefault)
			$this->orderByDefault[$name] = $orderByDefault;
		
		if ($datetime)
			$this->datetime[$name] = $datetime;

		if ($cascadeDelete && ($one || $many))
			$this->cascadeDelete[$name] = $cascadeDelete;
	}
	
	function surveySubclasses(ReflectionAnnotatedClass $class) {
		$parent = $class->getParentClass();
		if ($parent)
			$parent = $parent->getName();
		if ($annotation = $class->getAnnotation("data\\orm\\annotations\\Discriminate"))
			$this->discriminate = $annotation;
		elseif ($parent && $this->discriminate = Descriptor::getInstance($parent)->discriminate)
			$this->isSubclass = true;
	}

	function getPropertyAlias($property) {
		if (isset($this->alias[$property]))
			return $this->alias[$property];
		elseif ($this->isValidAlias($property))
			return $property;
	}
	
	function getAliasedDiscriminatorProperty() {
		return $this->getPropertyAlias($this->discriminate->property);
	}
	
	function getAliasedPersistedProperties() {
		$self = $this;
		return array_flatten(array_map(function($p) use($self){ if ($cp = @$self->compound[$p]) return $cp; else return $self->getPropertyAlias($p); }, $this->persisted));
	}
	
	function getSinglePK() {
		return @reset(array_keys($this->pk));
	}

	function getSinglePKAlias() {
		return $this->getPropertyAlias($this->getSinglePK());
	}
	
	function getPKSequence() {
		return @$this->pk[$this->generated]->sequence;
	}
	
	function getAliasedPK() {
		$self = $this;
		return array_flatten(array_map(function($p) use($self){ if ($cpk = @$self->compound[$p]) return $cpk; else return $p; }, array_keys($this->pk)));
	}
	
	function getBroughtProperties() {
		$properties = array();
		foreach(array_merge(array_keys($this->one), array_keys($this->many)) as $p)
			if (!in_array($p, $this->persisted))
				$properties[] = $p;
		return $properties;
	}
	
	function getDiscriminatorProperty() {
		if ($this->isSubclass)
			return $this->discriminate->property;
		else {
			$parent = get_parent_class($this->class);
			$descriptor = $parent::getDescriptor();
			if ($descriptor)
				return $descriptor->discriminate->property;
		}
	}
	
	function hasCompoundPK() {
		return count($this->pk) > 1;
	}
	
	function isValidProperty($property) {
		return in_array($property, $this->persisted);
	}
	
	function isValidAlias($alias) {
		if (in_array($alias, $this->alias))
			return true;
		else
			foreach($this->compound as $property=>$keys)
				if (in_array($alias, $keys))
					return true;
	}
	
	function isCompoundFK($property) {
		return isset($this->compound[$property]);
	}
	
	function isRelationToOne($property) {
		return isset($this->one[$property]);
	}

	function isRelationToMany($property) {
		return isset($this->many[$property]);
	}
	
	function isNotNullProperty($property) {
		return in_array($property, $this->notNull);
	}

	function isEmbeddedProperty($property) {
		return array_key_exists($property, $this->embedded);
	}

	function isEmbeddedPropertyRefOnly($property) {
		return isset($this->embedded[$property]->refOnly);
	}
	
	function isTransformedProperty($property) {
		return isset($this->transformed[$property]);
	}
	
	function hasCascadeDelete() {
		return count($this->cascadeDelete);
	}
	
	function isCascadeDeleteProperty($property) {
		return isset($this->cascadeDelete[$property]);
	}
	
	function getCascadeDeleteProperties() {
		return array_keys($this->cascadeDelete);
	}
}
