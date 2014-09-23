<?php

namespace entity\core;
use data\orm\Persistent;
use entity\validation\Validable;
use entity\validation\Validator;
use entity\validation\ValidationException;

abstract class Entity extends Persistent implements Validable {
	
	private static $descriptors = array();
	/** @data\orm\annotations\Transient */
	protected $lastValidationErrors = array();
	
	static function getValidationDescriptor() {
		return self::getEntityDescriptor()->validation;
	}
	
	static function getEntityDescriptor() {
		if (!@self::$descriptors[$class = get_called_class()])
			self::$descriptors[$class] = $config = new Descriptor($class);
		return self::$descriptors[$class];		
	}
	
	function __toString() {
		$self = $this;
		return interpolate($this->getEntityDescriptor()->stringRepresentation, function($key) use($self){
			return $self->$key;
		});
	}
	
	function isValid($property = null) {
		try {
			$this->validate($property);
			return true;
		} catch(ValidationException $e) {
			return false;
		}
	}
	
	function validate($property = null) {
		if (count($this->lastValidationErrors = is_null($property) ? Validator::validate($this) : Validator::validateProperty($this, $property)))
			throw new ValidationException($this->lastValidationErrors);
	}
	
	function getLastValidationErrors() {
		return $this->lastValidationErrors;
	}
	
	function save($refresh = true) {
		$this->validate();
		parent::save($refresh);
	}

	function update($refresh = true) {
		$this->validate();
		parent::update($refresh);
	}
	
}

?>