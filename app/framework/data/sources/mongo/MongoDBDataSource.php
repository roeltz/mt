<?php

namespace data\sources\mongo;
use data\core\DataSource;
use data\core\Criteria;
use data\core\Aggregate;
use data\core\Collection;
use data\core\exception as ex;
use data\core\support\DeepFields;
use foo\util\Logger;
use Mongo;
use MongoId;
use MongoDate;
use MongoDBRef;
use MongoConnectionException;
use MongoCode;
use DateTime;

class MongoDBDataSource implements DataSource, DeepFields {
	
	private $db;
	private $connection;
	private $queryBuilder;
	private $aggregateBuilder;
	private $logger;
	
	function __construct($db, $host = "localhost", $user = "", $password = "", array $options = array()) {
		try {
			$credentials = $user ? ("$user". ($password ? ":$password" : "") ."@") : "";
			$this->db = $db;
			$this->connection = new Mongo("mongodb://{$credentials}{$host}");
			$this->queryBuilder = new MongoDBQueryBuilder($this);
			$this->aggregateBuilder = new MongoDBAggregateBuilder($this);
			$this->logger = Logger::get(fnn(@$options['logger'], CFG_DATA_LOGGER));
		} catch(MongoConnectionException $e) {
			throw new ex\ConnectionException($e->getMessage());
		}
	}
		
	function criteria() {
		return new MongoDBCriteria(new Criteria($this));
	}
	
	function find(Criteria $criteria, array $fields = null, $return = DataSource::QUERY_RETURN_ALL) {
		$query = $this->queryBuilder->build($criteria);
		$this->log("find: " . json_encode($query), Logger::DEBUG);
		$start = microtime(true);
		$mdbfields = array();
		
		if ($fields)
			foreach($fields as $field)
				$mdbfields[$field] = true;

		$cursor = $this->connection->{$this->db}->{$criteria->collection->name}->find($query, $mdbfields);
		
		if (count($criteria->orders))
			$cursor->sort($this->queryBuilder->buildSort($criteria));
		
		if ($criteria->limit) {
			if ($criteria->limit->offset)
				$cursor->skip($criteria->limit->offset);
			$cursor->limit($criteria->limit->length);
		}

		$items = array_values(iterator_to_array($cursor));
		foreach($items as &$item)
			$this->processItem($item);

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
		$this->log(sprintf("Query took %.5fs", $end - $start), Logger::PROFILE);
		return $return;
	}
	
	function count(Criteria $criteria) {
		$start = microtime(true);
		$query = $this->queryBuilder->build($criteria);
		$this->log("count: " . json_encode($query), Logger::DEBUG);

		$count = $this->connection->{$this->db}->{$criteria->collection->name}->count($query);

		$end = microtime(true);
		$this->log(sprintf("Query took %.5fs", $end - $start), Logger::PROFILE);
		return $count;
	}
	
	function aggregate(Aggregate $aggregate, Criteria $criteria) {
		$start = microtime(true);
		$query = $this->queryBuilder->build($criteria);
		$this->log("count: " . json_encode($query), Logger::DEBUG);

		list($mapfn, $reducefn) = $this->aggregateBuilder->build($aggregate);
		$result = $this->connection->{$this->db}->command($command = array(
			'mapreduce'=>$criteria->collection->name,
			'query'=>count($query) ? $query : null,
			'map'=>$mapfn,
			'reduce'=>$reducefn,
			'out'=> array('inline'=>true)
		));
		if ($result['ok']) {
			$end = microtime(true);
			$this->log(sprintf("Query took %.5fs", $end - $start), Logger::PROFILE);
			return @$result['results'][0]['value'];
		}
		else
			$this->throwException($result['errmsg'] . (@$result['assertion'] ? ": " . $result['assertion'] : ""), $command);
	}
	
	function save(Collection $collection, array $values, $sequence = null, $autoincrement = null) {
		$this->recursiveEscape($values);
		$this->log("save: " . json_encode($values), Logger::DEBUG);
		$this->connection->{$this->db}->{$collection->name}->insert($values, array('safe'=>true));
		$id = $values["_id"] instanceof MongoId ? (string) $values["_id"] : $values["_id"];
		$this->log("Insert ID: $id", Logger::DEBUG);
		return $id;
	}
	
	function update(Criteria $criteria, array $values) {
		unset($values['_id']);
		$this->recursiveEscape($values);
		$query = $this->queryBuilder->build($criteria);
		$this->log("update (" . json_encode($query) . "): " . json_encode($values), Logger::DEBUG);
		$this->connection->{$this->db}->{$criteria->collection->name}->update($query, array('$set'=>$values), array('safe'=>true));
	}
	
	function delete(Criteria $criteria) {
		$query = $this->queryBuilder->build($criteria);
		$this->log("delete: " . json_encode($query), Logger::DEBUG);
		$this->connection->{$this->db}->{$criteria->collection->name}->remove($query, array('safe'=>true));		
	}
	
	function mapReduce(Criteria $criteria, $map, $reduce, array $scope = null) {
		$command = array(
			'mapreduce'=>$criteria->collection->name,
			'map'=>new MongoCode($map),
			'reduce'=>new MongoCode($reduce),
			'out'=>array('inline'=>1)
		);
		if ($criteria->expressions)
			$command['query'] = $this->queryBuilder->build($criteria);
		if ($criteria->orders)
			$command['sort'] = $this->queryBuilder->buildSort($criteria);
		if ($scope)
			$command['scope'] = $scope;
		
		$result = $this->connection->{$this->db}->command($command);

		if ($result['ok'])
			return $result['results'];
		else
			throw new ex\QueryException("{$result['errmsg']}: {$result['assertion']}");
	}
	
	function processItem(&$value) {
		$self = $this;
		$db = $this->connection->{$this->db};
		object_walk_recursive(function(&$value) use($self, $db) {
			if ($value instanceof MongoId) {
				$value = (string) $value;
				return false;
			} elseif ($value instanceof MongoDate) {
				$value = new DateTime(date("Y-m-d H:i:s", $value->sec));
				return false;
			} elseif (MongoDBRef::isRef($value)) {
				$value = $db->getDBRef($value);
			}
		}, $value);
		return $value;
	}

	function escape($value) {
		switch (gettype($value)) {
			case "object":
				if ($value instanceof DateTime) {
					return new MongoDate($value->getTimestamp());
				}
			default:
				return $value;
		}
	}
	
	function recursiveEscape(&$values) {
		$self = $this;
		object_walk_recursive(function(&$value) use($self){
			$value = $self->escape($value);
		}, $values);
	}
	
	private function throwException($error, $data = null) {
		$this->log($error, Logger::ERROR);
		throw new ex\QueryException($error, json_encode($data));
	}
	
	private function log($message, $level) {
		if ($this->logger)
			$this->logger->log("mongo: $message", $level);
	}
}
