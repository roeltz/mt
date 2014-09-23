<?php

namespace data\sources\mysql;
use mysqli;
use mysqli_result;
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

class MySQLDataSource implements DataSource, SQLDataSource, RelationalDataSource, TransactionalDataSource {
	
	const TYPE_TINYINT = 1;
	const TYPE_SMALLINT = 2;
	const TYPE_MEDIUMINT = 9;
	const TYPE_INT = 3;
	const TYPE_BIGINT = 8;
	const TYPE_DECIMAL = 246;
	const TYPE_FLOAT = 4;
	const TYPE_DOUBLE = 5;
	const TYPE_BIT = 16;
	const TYPE_DATE = 10;
	const TYPE_DATETIME = 12;
	const TYPE_TIMESTAMP = 7;
	const TYPE_TIME = 11;
	const TYPE_YEAR = 13;
	const TYPE_CHAR = 254;
	const TYPE_VARCHAR = 253;
	const TYPE_TEXT = 252;
	
	private $connection;
	private $generator;
	private $logger;
	
	function __construct($db, $host = "localhost", $user = "root", $password = "", array $options = array()) {
		$this->connection = @new mysqli("p:$host", $user, $password, $db);
		$this->logger = Logger::get(fnn(@$options['logger'], CFG_DATA_LOGGER));
		if (!$this->connection->connect_errno) {
			$this->generator = new MySQLGenerator($this);
			$this->connection->set_charset("utf8");
			$this->connection->autocommit(true);
		} else {
			$this->throwConnectionException();
		}
	}
	
	function query($query, array $parameters = null, $return = DataSource::QUERY_RETURN_ALL) {
		if ($parameters)
			$query = $this->generator->interpolate($query, $parameters);
		
		$this->log($query, Logger::DEBUG);
		$start = microtime(true);
		
		if ($result = $this->connection->query($query)) {
			$types = $this->getResultTypes($result);
			$items = array();
			while($item = $result->fetch_assoc()) {
				$this->processItem($item, $types);
				$items[] = $item;
			}
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
			$this->throwQueryException();
		}
	}
	
	function exec($query, array $parameters = null) {
		if ($parameters)
			$query = $this->generator->interpolate($query, $parameters);

		$this->log($query, Logger::DEBUG);
		if ($this->connection->query($query)) {
			return $this->connection->affected_rows;
		} else {
			$this->throwQueryException();
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
		$this->exec($this->generator->insert($collection, $values, $autoincrement));
		$this->log("Insert ID: {$this->connection->insert_id}", Logger::DEBUG);
		return $this->connection->insert_id;
	}
	
	function update(Criteria $criteria, array $values) {
		return $this->exec($this->generator->update($criteria, $values));
	}
	
	function delete(Criteria $criteria) {
		return $this->exec($this->generator->delete($criteria));
	}
	
	function beginTransaction() {
		$this->connection->autocommit(false);
	}
	
	function commit() {
		if (!$this->connection->commit())
			$this->throwQueryException();
		$this->connection->autocommit(true);
	}
	
	function rollback() {
		if (!$this->connection->rollback())
			$this->throwQueryException();
		$this->connection->autocommit(true);
	}
	
	function getConnection() {
		return $this->connection;
	}
	
	function processItem(array &$items, array &$types) {
		foreach($items as $field=>&$value)
			if (!is_null($value)) {
				switch($types[$field]) {
					case MySQLDataSource::TYPE_TINYINT:
					case MySQLDataSource::TYPE_SMALLINT:
					case MySQLDataSource::TYPE_MEDIUMINT:
					case MySQLDataSource::TYPE_INT:
					case MySQLDataSource::TYPE_BIGINT:
					case MySQLDataSource::TYPE_YEAR:
						$value = (int) $value;
						continue;
					case MySQLDataSource::TYPE_DOUBLE:
					case MySQLDataSource::TYPE_FLOAT:
					case MySQLDataSource::TYPE_DECIMAL:
						$value = (double) $value;
						continue;
					case MySQLDataSource::TYPE_DATE:
					case MySQLDataSource::TYPE_DATETIME:
					case MySQLDataSource::TYPE_TIMESTAMP:
						$value = new DateTime($value);
						continue;
				}
			}
	}
	
	function getResultTypes(mysqli_result $result) {
		$types = array();
		foreach($result->fetch_fields() as $meta)
			$types[$meta->name] = $meta->type;
		return $types;
	}
	
	private function log($message, $level) {
		if ($this->logger)
			$this->logger->log("mysql: $message", $level);
	}
	
	private function throwConnectionException() {
		$error = $this->connection->connect_error;
		$errno = $this->connection->connect_errno;
		//echo "ERROR {$errno} - {$error}\n";
		switch($errno) {
			case 1045:
			case 1130:
				throw new ex\AuthenticationException();
			case 1049:
				preg_match("/Unknown database '(.+)'/", $error, $m);
				throw new ex\InvalidSchemaException($m[1]);
			case 2002:
				throw new ex\UnavailableServiceException();
			default:
				throw new ex\ConnectionException($error);
		}
	}
	
	private function throwQueryException() {
		$error = $this->connection->error;
		$errno = $this->connection->errno;
		//echo "ERROR {$errno} - {$error}\n";
		$this->log($error, Logger::ERROR);
		switch($errno) {
			case 1054:
				preg_match("/Unknown column '(.*)' in/", $error, $m);
				throw new ex\InvalidFieldException($m[1]);
			case 1062:
				preg_match("/Duplicate entry '(.*)' for key '(.+)'/", $error, $m);
				throw new ex\DuplicateEntryException($m[1], $m[2]);
			case 1146:
				preg_match("/Table '(.*)' doesn't exist/", $error, $m);
				throw new ex\InvalidCollectionException($m[1]);
			default:
				throw new ex\QueryException($error);
		}
	}
}
