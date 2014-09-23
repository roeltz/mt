<?php

namespace data\sources\pgsql;
use data\core\Collection;
use data\util\GenericSQLGenerator;
use DateTime;

class PostgreSQLGenerator extends GenericSQLGenerator {
	
	private $dataSource;
	
	function __construct(PostgreSQLDataSource $dataSource) {
		$this->dataSource = $dataSource;
	}
	
	function insert(Collection $collection, array $values, $autoincrement = null) {
		$sql = parent::insert($collection, $values, $autoincrement);
		if ($autoincrement)
			$sql = "$sql RETURNING " . $this->escapeFieldName($autoincrement);
		return $sql;
	}
	
	function escapeValue($value) {
		if (is_string($value))
			return "'" . pg_escape_string($this->dataSource->getConnection(), $value) . "'";
		elseif ($value instanceof DateTime)
			return $this->escapeValue($value->format('Y-m-d H:i:s'));
		elseif (is_bool($value))
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
			$field = str_replace('"', '""', $field);
			return "\"$field\"";
		} 
	}
	
	function escapeTableName($collection) {
		$collection = str_replace('"', '""', $collection);
		return "\"$collection\"";
	}
	
	function getRandomOrderExpression() {
		return "RANDOM()";
	}
}
