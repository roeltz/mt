<?php

namespace data\sources\sqlsrv;
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

class SQLServerDataSource implements DataSource, SQLDataSource, RelationalDataSource, TransactionalDataSource {

	private $connection;
	private $generator;
	private $logger;
	
	function __construct($db, $host, $user, $password, array $options = array()) {
		$parameters = array(
			'CharacterSet'=>'UTF-8',
			'DataBase'=>$db,
			'UID'=>$user,
			'PWD'=>$password
		);
		$this->logger = Logger::get(fnn(@$options['logger'], CFG_DATA_LOGGER));
		if (($this->connection = sqlsrv_connect($host, $parameters))) {
			$this->generator = new SQLServerGenerator($this);
		} else {
			$this->throwException(false);
		}
	}
	
	function query($query, array $parameters = null, $return = DataSource::QUERY_RETURN_ALL) {
		if ($parameters)
			$query = $this->generator->interpolate($query, $parameters);
		
		$this->log($query, Logger::DEBUG);
		$start = microtime(true);
		
		if ($result = sqlsrv_query($this->connection, $query)) {
			$types = $this->getResultTypes($result);
			$items = array();
			while($item = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
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
			$this->throwException();
		}
	}
	
	function exec($query, array $parameters = null) {
		if ($parameters)
			$query = $this->generator->interpolate($query, $parameters);

		$this->log($query, Logger::DEBUG);
		if ($result = sqlsrv_query($this->connection, $query)) {
			return sqlsrv_rows_affected($result);
		} else {
			$this->throwException();
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
		$insertId = $this->query("SELECT SCOPE_IDENTITY() AS [LAST_INSERT_ID]", null, DataSource::QUERY_RETURN_VALUE);
		$this->log("Insert ID: {$insertId}", Logger::DEBUG);
		return $insertId;
	}
	
	function update(Criteria $criteria, array $values) {
		return $this->exec($this->generator->update($criteria, $values));
	}
	
	function delete(Criteria $criteria) {
		return $this->exec($this->generator->delete($criteria));
	}
	
	function beginTransaction() {
		if (!sqlsrv_begin_transaction($this->connection))
			$this->throwException();
	}
	
	function commit() {
		if (!sqlsrv_commit($this->connection))
			$this->throwException();
	}
	
	function rollback() {
		if (!sqlsrv_rollback($this->connection))
			$this->throwException();
	}
	
	function getConnection() {
		return $this->connection;
	}
	
	function processItem(array &$items, array &$types) {
		foreach($items as $field=>&$value)
			if (!is_null($value)) {
				switch($types[$field]) {
					case SQLSRV_SQLTYPE_BIGINT:
					case SQLSRV_SQLTYPE_INT:
					case SQLSRV_SQLTYPE_SMALLINT:
					case SQLSRV_SQLTYPE_TINYINT:
						$value = (int) $value;
						continue;
					case SQLSRV_SQLTYPE_FLOAT:
					case SQLSRV_SQLTYPE_MONEY:
					case SQLSRV_SQLTYPE_REAL:
						$value = (double) $value;
						continue;
					case SQLSRV_SQLTYPE_DATE:
					case SQLSRV_SQLTYPE_DATETIME:
					case SQLSRV_SQLTYPE_DATETIME2:
					case SQLSRV_SQLTYPE_SMALLDATETIME:
						$value = new DateTime($value);
						continue;
				}
			}
	}
	
	function getResultTypes($result) {
		$metadata = sqlsrv_field_metadata($result);
		$types = array();
		foreach($metadata as $meta)
			$types[$meta['Name']] = $meta['Type'];
		return $types;		
	}
	
	private function log($message, $level) {
		if ($this->logger)
			$this->logger->log("sqlsrv: $message", $level);
	}
	
	private function throwException($query = true) {
		$error = reset(sqlsrv_errors());
		$message = $error['message'];
		$this->log($message, Logger::ERROR);
		switch($error['SQLSTATE']) {
			case 'IMSSP':
			case '08001':
				throw new ex\ConnectionException($message);
			case '28000':
				throw new ex\AuthenticationException();
			case '42S02':
				preg_match('/\'([^\']+)\'/', $message, $m);
				throw new ex\InvalidCollectionException($m[1]);
			case '42S22':
				preg_match('/\'([^\']+)\'/', $message, $m);
				throw new ex\InvalidFieldException($m[1]);
			default:
				if ($query)
					throw new ex\QueryException($message);
				else
					throw new ex\DataException($message);
		}
	}
}
