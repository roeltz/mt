<?php

namespace data\core;
use foo\util\Logger;

interface DataSource {
	
	const QUERY_RETURN_ALL = "all";
	const QUERY_RETURN_SINGLE = "single";
	const QUERY_RETURN_SERIES = "series";
	const QUERY_RETURN_VALUE = "value";
	
	function criteria();	
	function find(Criteria $criteria, array $fields = null, $return = DataSource::QUERY_RETURN_ALL);
	function count(Criteria $criteria);
	function aggregate(Aggregate $aggregate, Criteria $criteria);
	
	function save(Collection $collection, array $values, $sequence = null, $autoincrement = null);
	function update(Criteria $criteria, array $values);
	function delete(Criteria $criteria);
}
