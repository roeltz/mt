<?php

namespace data\sources\pgsql;
use DateTime;
use data\core\DataSource;
use data\core\SQLDataSource;
use data\core\TransactionalDataSource;
use data\core\RelationalDataSource;
use data\core\Criteria;
use data\core\Aggregate;
use data\core\Collection;
use data\core\exception as ex;
use foo\util\Logger;

class PostgreSQLDataSource implements DataSource, SQLDataSource, RelationalDataSource, TransactionalDataSource {
	
	const TYPE_INT2 = "int2";
	const TYPE_INT4 = "int4";
	const TYPE_INT8 = "int8";
	const TYPE_BPCHAR = "bpchar";
	const TYPE_VARCHAR = "varchar";
	const TYPE_TEXT = "text";
	const TYPE_FLOAT4 = "float4";
	const TYPE_FLOAT8 = "float8";
	const TYPE_NUMERIC = "numeric";
	const TYPE_TIMESTAMP_TZ = "timestamptz";
	const TYPE_TIMESTAMP = "timestamp";
	const TYPE_BOOL = "bool";
	const TYPE_BYTE_ARRAY = "bytea";

	private $connection;
	private $generator;
	private $logger;
	
	function __construct($db, $host = "localhost:5432", $user = "postgres", $password = "", array $options = array()) {
		@list($host, $port) = explode(":", $host);
		if (!$port) $port = 5432;
		if ($this->connection = @pg_pconnect("dbname=$db host=$host port=$port user=$user password=$password")) {
			$this->generator = new PostgreSQLGenerator($this);
			$this->logger = Logger::get(fnn(@$options['logger'], CFG_DATA_LOGGER));
			pg_set_client_encoding($this->connection, "UTF-8");			
		} else {
			throw new ex\ConnectionException(__("Could not connect to database"));
		}
	}
	
	function query($query, array $parameters = null, $return = DataSource::QUERY_RETURN_ALL) {
		if ($parameters)
			$query = $this->generator->interpolate($query, $parameters);

		$this->log($query, Logger::DEBUG);
		$start = microtime(true);

		pg_send_query($this->connection, $query);
		$result = pg_get_result($this->connection);
		$sqlState = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);
		if (!$sqlState) {
			$types = $this->getResultTypes($result);
			$items = array();
			while ($item = pg_fetch_assoc($result)) {
				$this->processItem($item, $types);
				$items[] = $item;
			}
			pg_free_result($result);
			$count = count($items);
			
			switch($return) {
				case DataSource::QUERY_RETURN_SERIES:
					$series = array();
					foreach($items as $i)
						$series[] = reset($i);
					$return = &$series;
					break;
				
				case DataSource::QUERY_RETURN_SINGLE:
					$return = @$items[0];
					break;
				
				case DataSource::QUERY_RETURN_VALUE:
					$return = @reset($items[0]);
					break;
				
				default:
					$return = &$items;
			}
			$end = microtime(true);
			$this->log(sprintf("Query took %.5fs, returned %d element(s)", $end - $start, $count), Logger::PROFILE);
			return $return;
		} else {
			$this->throwException($sqlState);
		}
	}
	
	function exec($query, array $parameters = null, $return = DataSource::QUERY_RETURN_ALL) {
		$this->log($query, Logger::DEBUG);
		pg_send_query($this->connection, $query);
		$result = pg_get_result($this->connection);
		$sqlState = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);
		if (!$sqlState) {
			return pg_affected_rows($result);
		} else {
			$this->throwQueryException($sqlState);
		}
	}

	function criteria() {
		return new Criteria($this);
	}
	
	function find(Criteria $criteria, array $fields = null, $return = DataSource::QUERY_RETURN_ALL) {
		return $this->query($this->generator->select($criteria, $fields), $fields, $return);
	}
	
	function count(Criteria $criteria) {
		return $this->query($this->generator->count($criteria), null, DataSource::QUERY_RETURN_VALUE);
	}
	
	function aggregate(Aggregate $aggregate, Criteria $criteria) {
		return $this->query($this->generator->aggregate($aggregate, $criteria), null, DataSource::QUERY_RETURN_VALUE);
	}
	
	function save(Collection $collection, array $values, $sequence = null, $autoincrement = null) {
		$id = $this->query($this->generator->insert($collection, $values, $autoincrement), null, DataSource::QUERY_RETURN_VALUE);
		if ($sequence) {
			$sequence = $this->generator->escapeValue($sequence);
			$id = current(pg_fetch_assoc(pg_query("SELECT currval($sequence)")));
		}
		$this->log("Insert ID: {$id}", Logger::DEBUG);
		return $id;
	}
	
	function update(Criteria $criteria, array $values) {
		return $this->exec($this->generator->update($criteria, $values));
	}
	
	function delete(Criteria $criteria) {
		return $this->exec($this->generator->delete($criteria));
	}
	
	function beginTransaction() {
		pg_query($this->connection, "BEGIN");
	}
	
	function commit() {
		pg_query($this->connection, "COMMIT");
	}
	
	function rollback() {
		pg_query($this->connection, "ROLLBACK");
	}
	
	function getConnection() {
		return $this->connection;
	}
	
	function processItem(array &$items, array &$types) {
		foreach($items as $field=>&$value)
			if (!is_null($value)) {
				switch($types[$field]) {
					case PostgreSQLDataSource::TYPE_INT2:
					case PostgreSQLDataSource::TYPE_INT4:
					case PostgreSQLDataSource::TYPE_INT8:
						$value = (int) $value;
						continue;
					case PostgreSQLDataSource::TYPE_FLOAT4:
					case PostgreSQLDataSource::TYPE_FLOAT8:
					case PostgreSQLDataSource::TYPE_NUMERIC:
						$value = (double) $value;
						continue;
					case PostgreSQLDataSource::TYPE_TIMESTAMP:
					case PostgreSQLDataSource::TYPE_TIMESTAMP_TZ:
						$value = new DateTime($value);
						continue;
					case PostgreSQLDataSource::TYPE_BOOL:
						$value = $value == 't';
						continue;
				}
			}
	}
	
	function getResultTypes($result) {
		$types = array();
		for($i = 0, $n = pg_num_fields($result); $i < $n; $i++)
			$types[pg_field_name($result, $i)] = pg_field_type($result, $i);
		return $types;
	}
	
	private function log($message, $level) {
		$this->logger->log("pgsql: $message", $level);
	}
	
	function throwException($sqlState) {
		$error = pg_last_error();
		//echo "SQLSTATE $sqlState \n$error\n";
		$this->log($error, Logger::ERROR);
		switch($sqlState) {
			case "42P01":
				preg_match("/«([^»]+)»/", $error, $m);
				throw new ex\InvalidCollectionException($m[1]);
			case "42703":
				preg_match("/«([^»]+)»/", $error, $m);
				throw new ex\InvalidFieldException($m[1]);
			case "23505":
				preg_match("/\(([^)]+)\)=\(([^)]+)\)/", $error, $m);
				throw new ex\DuplicateEntryException($m[2], $m[1]);
			default:
				throw new ex\QueryException($error);
		}
	}
}
