<?php

namespace data\sources\mysql;
use data\core\Collection;
use data\util\GenericSQLGenerator;
use DateTime;
use DateTimeZone;

class MySQLGenerator extends GenericSQLGenerator {
	
	private $dataSource;
	
	function __construct(MySQLDataSource $dataSource) {
		$this->dataSource = $dataSource;
	}
	
	function escapeValue($value) {
		if (is_string($value))
			return "'" . $this->dataSource->getConnection()->escape_string($value) . "'";
		elseif ($value instanceof DateTime) {
			if ($value->getOffset() != 0) {
				$value = clone $value;
				$value->setTimezone(new DateTimeZone("UTC"));
			}
			return $this->escapeValue($value->format('Y-m-d H:i:s'));
		} elseif (is_bool($value))
			return $value ? "TRUE" : "FALSE";
		elseif (is_null($value))
			return "NULL";
		elseif (is_object($value))
			return $this->escapeValue((string) $value);
		else
			return $value;
	}
	
	function escapeFieldName($field) {
		if (strstr($field, ".")) {
			list($c, $f) = explode(".", $field);
			return $this->escapeTableName($c) .".". $this->escapeFieldName($f);
		} else {
			return "`$field`";
		} 
	}
	
	function escapeTableName($collection) {
		return "`$collection`";
	}
}
