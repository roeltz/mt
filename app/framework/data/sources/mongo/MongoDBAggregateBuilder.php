<?php

namespace data\sources\mongo;
use data\core\Criteria;
use data\core\Aggregate;
use MongoCode;

class MongoDBAggregateBuilder {
	
	function build(Aggregate $aggregate) {
		
		switch($aggregate->operation) {
			case Aggregate::SUM:
				$mapfn = new MongoCode( 
<<<MAP
function() {
	var value = this.{$aggregate->field};
	if (value instanceof Object) {
		var sum = 0;
		for(var i in value)
			sum += parseFloat(value[i]);
		value = sum;
	} else {
		value = parseFloat(value);
	}
	emit("{$aggregate->field}", value);
}
MAP
				);
				$reducefn = new MongoCode(
<<<REDUCE
function(k, values) {
	var r = 0;
	values.forEach(function(v){ r += v });
	return r;
}
REDUCE
				);
				break;

			case Aggregate::AVG:
				$mapfn = new MongoCode( 
<<<MAP
function() {
	emit("{$aggregate->field}", parseFloat(this.{$aggregate->field}));
}
MAP
				);
				$reducefn = new MongoCode(
<<<REDUCE
function(k, values) {
	var r = 0;
	values.forEach(function(v){ r += v });
	return r / (values.length || 1);
}
REDUCE
				);
				break;

			case Aggregate::MAX:
				$mapfn = new MongoCode( 
<<<MAP
function() {
	emit("{$aggregate->field}", parseFloat(this.{$aggregate->field}));
}
MAP
				);
				$reducefn = new MongoCode(
<<<REDUCE
function(k, values) {
	return Math.max.apply(Math, values);
}
REDUCE
				);
				break;

			case Aggregate::MIN:
				$mapfn = new MongoCode( 
<<<MAP
function() {
	emit("{$aggregate->field}", parseFloat(this.{$aggregate->field}));
}
MAP
				);
				$reducefn = new MongoCode(
<<<REDUCE
function(k, values) {
	return Math.min.apply(Math, values);
}
REDUCE
				);
				break;
		}
		
		return array($mapfn, $reducefn);
	}
}
