<?php

namespace data\sources\json;
use foo\util\Logger;
use data\core\DataSource;
use data\core\Criteria;
use data\core\Aggregate;
use data\core\Collection;
use data\core\Restrict;
use data\util\GenericCriteriaEvaluator;

class JSONDataSource implements DataSource {
	protected $basePath;
	
	function __construct($basePath = null) {
		if ($basePath)
			$this->basePath = "$basePath/";
	}
	
	function criteria() {
		return new Criteria($this);
	}
	
	function find(Criteria $criteria, array $fields = null, $return = DataSource::QUERY_RETURN_ALL) {
		$result = array();
		$this->map($criteria, function($data) use(&$result){
			$result[] = $data;
		});
		
		switch($return) {
			case DataSource::QUERY_RETURN_SERIES:
				$series = array();
				foreach($result as $i)
					$series[] = reset($i);
				$return = &$series;
				break;
			
			case DataSource::QUERY_RETURN_SINGLE:
				$return = @$result[0];
				break;
			
			case DataSource::QUERY_RETURN_VALUE:
				$return = @reset($result[0]);
				break;
			
			default:
				$return = &$result;
		}
		
		return $return;
	}
	
	function count(Criteria $criteria) {
		$result = 0;
		$this->map($criteria, function($data) use(&$result){
			$result++;
		});
		return $result;
	}
	
	function aggregate(Aggregate $aggregate, Criteria $criteria) {
		$field = $aggregate->field;
		$values = array();
		$this->map($criteria, function($data) use(&$values, $field){
			$values[] = $data[$field];
		});

		switch($aggregate->operation) {
			case Aggregate::MIN:
				return min($values);
			case Aggregate::MAX:
				return max($values);
			case Aggregate::SUM:
				return array_sum($values);
			case Aggregate::SUM:
				return array_sum($values) / count($values);
		}
		
		return 0;
	}
	
	function save(Collection $collection, array $values, $sequence = null, $autoincrement = null) {
		$path = fill($collection->name, $values);
		file_put_contents("{$this->basePath}$path", json_encode($values));
	}
	
	function update(Criteria $criteria, array $values) {
		$basePath = $this->basePath;
		$this->map($criteria, function($data, $path) use(&$values, $basePath){
			$oldData = json_decode(file_get_contents("$basePath$path"));
			$data = array_merge($oldData, $data);
			file_put_contents("$basePath$path", json_encode($data));
		});
	}
	
	function delete(Criteria $criteria) {
		$basePath = $this->basePath;
		$this->map($criteria, function($data, $path) use(&$values, $basePath){
			unlink("$basePath$path");
		});		
	}
	
	protected function map(Criteria $criteria, $callback) {
		$pattern = preg_replace('/{[^}]+}/', '*', "{$this->basePath}{$criteria->collection->name}");
		$evaluator = new GenericCriteriaEvaluator();
		foreach(glob($pattern) as $file) {
			$data = json_decode(file_get_contents($file), true);
			if ($evaluator->evaluate($data, Restrict::conj($criteria->expressions)))
				$callback($data, $file);
		}
	}
}
