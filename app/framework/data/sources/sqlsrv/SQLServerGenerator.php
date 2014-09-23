<?php

namespace data\sources\sqlsrv;
use data\core\Collection;
use data\util\GenericSQLGenerator;
use DateTime;
use DateTimeZone;

class SQLServerGenerator extends GenericSQLGenerator {
	
	private $dataSource;
	
	function __construct(SQLServerDataSource $dataSource) {
		$this->dataSource = $dataSource;
	}
	
	function escapeValue($value) {
		if (is_string($value))
			return "'" . str_replace("'", "''", $value) . "'";
		elseif ($value instanceof DateTime) {
			if ($value->getOffset() != 0) {
				$value = clone $value;
				$value->setTimezone(new DateTimeZone("UTC"));
			}
			return $this->escapeValue($value->format('Ymd H:i:s'));
		} elseif (is_bool($value))
			return $value ? 1 : 0;
		elseif (is_null($value))
			return "NULL";
		elseif (is_object($value))
			return $this->escapeValue((string) $value);
		else
			return $value;
	}
	
	function escapeFieldName($field) {
		return "[" . join("].[",  explode(".", $field)) . "]";
	}
	
	function escapeTableName($collection) {
		return "[" . join("].[",  explode(".", $collection)) . "]";
	}
	
	function insert(Collection $collection, array $values, $autoincrement = null) {
		$sql = parent::insert($collection, $values);
		return $sql . "; SELECT SCOPE_IDENTITY() AS [INSERT_ID]";
	}
}
