<?php

namespace mt\exporter\pipa;
use mt\Exporter;
use mt\output\FilesystemOutput;
use mt\Model;
use mt\Entity;
use mt\Attribute;
use mt\Type;
use mt\util\php\PHPClass;
use mt\util\php\Type as PHPType;
use mt\util\php\Member;
use mt\util\php\Property;
use mt\util\php\Parameter;
use mt\util\php\Constant;
use mt\util\php\Annotation;
use mt\util\php\SourceCode;

class PipaExporter implements Exporter {

	protected $extend;
	protected $useAssertions;
	protected $pgseq;
	protected $only;

	function __construct($extend = null, $assertions = true, $pgseq = false, $only = null) {
		$this->extend = $extend ? $extend : 'Pipa\ORM\Entity';
		$this->useAssertions = $assertions;
		$this->pgseq = $pgseq;
		if ($only)
			$this->only = explode(',', $only);
	}

	function getOutput(Model $model) {
		$output = new FilesystemOutput;

		$classes = array();

		foreach($model->entities as $entity)
			$classes[$entity->name] = $this->entityToClass($entity);

		foreach($classes as $entity=>$class)
			$this->resolveClassRelations($class, $model->entities[$entity], $classes);

		foreach($classes as $entity=>$class) {
			$this->resolveCreateMethod($class, $model->entities[$entity], $classes);
			$this->resolveEditMethod($class, $model->entities[$entity], $classes);
		}

		foreach($classes as $entity=>$class) {
			$this->resolveMisc($class, $model->entities[$entity], $classes);
		}

		foreach($classes as $class) {
			if (!$this->only || in_array($class->name, $this->only)) {
				$source = new SourceCode($class);
				$output->addFile($this->getFilePath($class), (string) $source);
			}
		}

		return $output;
	}

	function getDefaultOuputPath($path) {
		return preg_replace('/\\.\w+$/', '', $path);
	}

	private function entityToClass(Entity $entity) {
		$class = new PHPClass(fnn(@$entity->meta['entity'], $entity->name), $this->getNamespace($entity));

		if ($this->extend) {
			preg_match('/^(.+)?\\\\(\w+)$/', $this->extend, $m);
			$class->inherit(new PHPClass($m[2], $m[1] ? $m[1] : null));
		}

		$class->docBlock->comments = $entity->comments;
		$class->docBlock->addAnnotation(new Annotation('Collection', $entity->name));

		foreach($entity->attributes as $attribute) {
			$property = $this->attributeToProperty($attribute, $class);
			$class->addProperty($property);
		}

		return $class;
	}

	private function resolveClassRelations(PHPClass $class, Entity $entity, array $classes) {

		foreach($entity->relations as $relation) {

			$oneAnnotation = new Annotation('One');
			$relatedClass = str_replace('\\', '\\\\', $classes[$relation->foreignEntityName]->getRelativeName($class, $isFQN));
			if ($isFQN) $relatedClass = "\\\\$relatedClass";
			$oneAnnotation->addParameter("class", $relatedClass);

			if ($relation->hasCompoundKey()) {

				foreach($relation->keys as $local=>$foreign)
					unset($class->properties[$this->getAttributeName($entity->attributes[$local])]);

				if (!($propertyName = @$relation->meta['one']))
					$propertyName = join("_", $relation->keys);

				$property = new Property($propertyName);

				$oneAnnotation->addParameter("fk", array_keys($relation->keys));

			} else {

				$localAttribute = key($relation->keys);

				if (!($property = @$class->properties[$localAttribute]))
					$property = new Property($this->getAttributeName($attribute));

				if ($property->name != $localAttribute)
					$oneAnnotation->addParameter("fk", $localAttribute);
			}

			if ($entity->attributes[$property->name]->isPrimaryKey())
				$property->docBlock->getAnnotation("Id")->removeParameter("auto");
			elseif (!$relation->isNullable())
				$property->docBlock->addAnnotation(new Annotation("NotNull"), true);

			$property->docBlock->removeAnnotation("assert\\*");

			$property->docBlock->addAnnotation($oneAnnotation);
			$class->addProperty($property);
		}

		if ($many = @$entity->meta['many']) {
			$properties = preg_split('/\s*;\s*/', $many);

			foreach($properties as $property) {
				@list($property, $where) = preg_split('/\s*\?\s*/', $property);
				@list($property, $relatedEntity, $sort) = preg_split('/\s+/', $property);
				
				if (!$relatedEntity) {
					$relatedEntity = $property;
					$property = null;
				}

				@list($relatedEntity, $fk, $subproperty) = preg_split('/\s*:\s*/', $relatedEntity);
				$fk = preg_split('/\s*,\s*/', $fk);

				if (!$property)
					$property = $relatedEntity;
				
				if (count($fk) == 1)
					$fk = $fk[0];
				
				if ($sort) {
					$type = $sort[0] == "+" ? 1 : -1;
					$sortProperty = substr($type, 1);
					$sort = array($sortProperty=>$type);
				}
				
				if ($where) {
					$expressions = preg_split('/\s*,\s*/', $where);
					$restrictions = array();
					foreach($expressions as $expression) {
						@list($key, $value) = preg_split('/\s*=\s*/', $expression);
						if ($value == "this") {
							$restrictions[$key] = "this";
						} elseif (strlen($value) && ($value = @json_decode($value))) {
							$restrictions[$key] = $value;
						} else {
							$restrictions[$key] = true;
						}
					}
				}

				$property = new Property($property);
				$annotation = new Annotation("Many");
				$relativeClass = str_replace('\\', '\\\\', $classes[$relatedEntity]->getRelativeName($class, $isFQN));
				if ($isFQN) $relativeClass = "\\\\$relativeClass";
				
				$annotation->addParameter("class", $relativeClass);
				$annotation->addParameter("fk", $fk);
				if (@$restrictions)
					$annotation->addParameter("where", $restrictions);
				if (@$subproperty)
					$annotation->addParameter("property", $subproperty);
				if ($sort)
					$annotation->addParameter("order", $sort);
				$property->docBlock->addAnnotation($annotation);
				$class->addProperty($property);
			}
		}
	}

	private function attributeToProperty(Attribute $attribute, PHPType $type = null) {
		$property = new Property($this->getAttributeName($attribute), Member::ACCESS_PUBLIC, false, $attribute->defaultValue);

		if ($attribute->isPrimaryKey()) {
			$property->docBlock->addAnnotation(new Annotation("Id"));
			if ($attribute->autoincrement) {
				$annotation = new Annotation("Generated");
				if ($this->pgseq)
					$annotation->addParameter("value", "{$attribute->entity->name}_{$attribute->name}_seq");
				$property->docBlock->addAnnotation($annotation);
			}
		} elseif (!$attribute->nullable)
			$property->docBlock->addAnnotation(new Annotation("NotNull"));

		if (!is_null($attribute->defaultValue) && !$attribute->autoincrement) {
			$property->defaultValue = $attribute->defaultValue;
			$property->hasDefaultValue = true;
		}
		
		if ($type && $attribute->type->name == Type::ENUM) {
			foreach($attribute->type->arguments as $enumerable) {
				$name = str_replace('-', '_', strtoupper(from_camel_case($property->name, "_"))."_".strtoupper(from_camel_case($enumerable)));
				$type->addConstant(new Constant($name, $enumerable));
			}
		}
		
		if (isset($attribute->meta['order'])) {
			if ($order = $attribute->meta['order'])
				$annotation = new Annotation("OrderByDefault", $order);
			else
				$annotation = new Annotation("OrderByDefault");
			
			$property->docBlock->addAnnotation($annotation);
		}

		if (isset($attribute->meta['transform'])) {
			$annotation = new Annotation("Transform", $attribute->meta['transform']);
			$property->docBlock->addAnnotation($annotation);
		}

		return $property;
	}

	private function resolveCreateMethodParameters($meta, Entity $entity, array $classes) {
		$properties = preg_split('/\s*,\s*/', $meta);
		$parameters = array();

		foreach($properties as $property) {
			$defaultValue = null;
			$hasDefaultValue = false;
			if (preg_match('/^(\w+)\s+(.+)$/', $property, $m)) {
				$hasDefaultValue = true;
				list(, $property, $defaultValue) = $m;
			}

			$typeHint = null;
			$attribute = $this->getAttributeFromPropertyName($entity, $property);

			if ($attribute)
				foreach($entity->relations as $relation)
					if (array_key_exists($attribute->name, $relation->keys)) {
						$typeHint = $classes[$relation->foreignEntityName]->getFullyQualifiedName();
						break;
					} elseif ($attribute->type->isDate()) {
						$typeHint = "DateTime";
						break;
					}

			if ($hasDefaultValue) {
				list($resetTypeHint, $defaultValue) = $this->resolveParameterDefaultValue($defaultValue);
				$parameters[] = new Parameter($property, $typeHint ? $typeHint : $resetTypeHint, $defaultValue);
			} else
				$parameters[] = new Parameter($property, $typeHint);
		}

		return $parameters;		
	}

	private function resolveCreateMethod(PHPClass $class, Entity $entity, array $classes) {
		if (!isset($entity->meta['createUsing'])) return;
		$parameters = $this->resolveCreateMethodParameters($entity->meta['createUsing'], $entity, $classes);
		$class->addMethod($create = new CreateMethod($parameters));
		$create->populate();
	}

	private function resolveEditMethod(PHPClass $class, Entity $entity, array $classes) {
		if (!isset($entity->meta['editUsing'])) return;
		$parameters = $this->resolveCreateMethodParameters($entity->meta['editUsing'], $entity, $classes);
		$class->addMethod($create = new EditMethod($parameters));
		$create->populate();
	}
	
	private function resolveMisc(PHPClass $class, Entity $entity, array $classes) {
		if ($eager = @$entity->meta['eager']) {
			$properties = preg_split('/\s*,\s*/', $eager);
			foreach($properties as $property) {
				$class->properties[$property]->docBlock->addAnnotation(new Annotation("Eager"));
			}
		}
		
		if ($computed = @$entity->meta['computed']) {
			$properties = preg_split('/\s*,\s*/', $computed);
			foreach($properties as $property) {
				$property = new Property($property);
				$method = new ComputedMethod($property);
				$property->docBlock->addAnnotation(new Annotation("Computed", $method->name));
				$class->addProperty($property);
				$class->addMethod($method);
				$method->populate();
			}
		}
	}

	private function getAttributeName(Attribute $attribute) {
		return fnn(@$attribute->meta['alias'], $attribute->name);
	}

	private function getAttributeFromPropertyName(Entity $entity, $propertyName) {
		foreach($entity->attributes as $attribute)
			if ($attribute->name == $propertyName || @$attribute->meta['alias'] == $propertyName)
				return $attribute;
	}

	private function resolveParameterDefaultValue($value) {
		switch($value) {
			case "null":
				return array(null, null);
			case "true":
				return array(null, true);
			case "false":
				return array(null, false);
			case "#now":
				return array("DateTime", array('assign'=>"new DateTime()"));
			case "#hash":
				return array(null, array('assign'=>"sha1(uniqid(true))"));
			default:
				return array(null, $value);
		}
	}

	private function getNamespace(Entity $entity) {
		$namespaces = array();

		if ($ns = @$entity->model->meta['namespace'])
			$namespaces[] = $ns;
		if ($ns = @$entity->meta['namespace'])
			$namespaces[] = $ns;

		if ($namespaces)
			return join("\\", $namespaces);
	}

	private function getFilePath(PHPClass $class) {
		$path = "{$class->name}.php";
		if ($class->namespace)
			$path = str_replace('\\', '/', $class->namespace) . "/$path";
		return $path;
	}
}
