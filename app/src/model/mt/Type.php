<?php

namespace mt;

class Type {
	
	const TINYINT = "tinyint";
	const SMALLINT = "smallint";
	const INTEGER = "integer";
	const BIG_INTEGER = "big-integer";
	const DOUBLE = "double";
	const CHAR = "char";
	const VARCHAR = "varchar";
	const TEXT = "text";
	const DATETIME = "datetime";
	const DATE = "date";
	const TIME = "time";
	const BOOLEAN = "boolean";
	const ENUM = "enum";
	const CUSTOM = "custom";
	
	public $name;
	public $length;
	public $arguments;
	
	function __construct($name, $length = 0, $arguments = null) {
		$this->name = $name;
		$this->length = $length;
		$this->arguments = $arguments;
	}
	
	static function enum(array $values) {
		return new Type(self::ENUM, 0, $values);
	}
	
	function isEnum() {
		return $this->name === self::ENUM;
	}
	
	function isNumeric() {
		return in_array($this->name, array(
			self::BIG_INTEGER,
			self::DOUBLE,
			self::INTEGER,
			self::SMALLINT,
			self::TINYINT
		)); 
	}
	
	function isInteger() {
		return in_array($this->name, array(
			self::BIG_INTEGER,
			self::INTEGER,
			self::SMALLINT,
			self::TINYINT
		)); 		
	}
	
	function isDate() {
		return in_array($this->name, array(
			self::DATE,
			self::DATETIME,
			self::TIME
		)); 				
	}

	function isString() {
		return in_array($this->name, array(
			self::TEXT,
			self::VARCHAR,
			self::CHAR
		)); 				
	}
}
