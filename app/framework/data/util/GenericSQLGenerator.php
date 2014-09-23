<?php

namespace data\util;
use data\core\Criteria;
use data\core\Expression;
use data\core\Aggregate;
use data\core\criterion\Order;
use data\core\Collection;
use data\core\expression as expr;


abstract class GenericSQLGenerator {
	
	abstract function escapeValue($value);
	abstract function escapeTableName($table);
	abstract function escapeFieldName($field);
	
	function getRandomOrderExpression() {
		return "RAND()";
	}
	
	function interpolate($sql, array $parameters) {
		$self = $this;
		return \interpolate($sql, function($key) use($self, &$parameters){
			if (preg_match('/^\'/', $key))
				return $self->escapeFieldName(@$parameters[$key]);
			else
				return $self->escapeValue(@$parameters[$key]);
		});
	}
	
	function select(Criteria $criteria, array $fields = null) {
		$self = $this;

		if (is_null($fields))
			$fields = "*";
		else {
			if ($criteria->distinct)
				$distinct = "DISTINCT ";
			$fields = join(", ", array_map(function($f) use($self, $criteria){
				if ($criteria->collection->alias)
					return $self->escapeTableName($criteria->collection->alias) . "." . $self->escapeFieldName($f);
				else
					return $self->escapeFieldName($f);
			}, $fields));
		}

		$collection = $this->renderCollection($criteria->collection);
		
		if ($conditions = $this->renderCriteria($criteria))
			$where = " WHERE $conditions";
		
		if (count($criteria->orders)) {
			$o = join(", ", array_map(function($o) use($self){
				switch($o->type) {
					case Order::ASC:
					case Order::DESC:
						return $self->escapeFieldName($o->field) . " " . strtoupper($o->type);
					case Order::RANDOM:
						return $this->getRandomOrderExpression();	
				}
			}, $criteria->orders));
			$order = " ORDER BY $o";
		}
		
		if ($criteria->limit) {
			$offset = $this->escapeValue($criteria->limit->offset);
			$length = $this->escapeValue($criteria->limit->length);
			$limit = " LIMIT $length OFFSET $offset";
		}
		
		return @"SELECT $distinct$fields FROM $collection$where$order$limit";
	}
	
	function count(Criteria $criteria) {
		return preg_replace('/^SELECT \\*/', 'SELECT COUNT(*)', $this->select($criteria));
	}

	function aggregate(Aggregate $aggregate, Criteria $criteria) {
		$field = $this->escapeFieldName($aggregate->field);
		$operation = strtoupper($aggregate->operation);
		return preg_replace('/^SELECT \\*/', "SELECT $operation($field)", $this->select($criteria));
	}

	function insert(Collection $collection, array $values, $autoincrement = null) {
		$table = $this->escapeTableName($collection->name);
		$fields = array();
		$vals = array();
		foreach($values as $field=>$value) {
			$fields[] = $this->escapeFieldName($field);
			$vals[] = $this->escapeValue($value);
		}
		$fields = join(", ", $fields);
		$vals = join(", ", $vals);
		
		return @"INSERT INTO $table ($fields) VALUES ($vals)";
	}
	
	function update(Criteria $criteria, array $values) {
			
		$table = $this->escapeTableName($criteria->collection->name);
		
		$vars = array();
		foreach($values as $field=>$value)
			$vars[] = $this->escapeFieldName($field) . " = " . $this->escapeValue($value);
		$vars = join(", ", $vars);

		if ($conditions = $this->renderCriteria($criteria))
			$where = " WHERE $conditions";
		
		return @"UPDATE $table SET $vars$where";
	}
	
	function delete(Criteria $criteria) {
		$table = $this->escapeTableName($criteria->collection->name);

		if ($conditions = $this->renderCriteria($criteria))
			$where = " WHERE $conditions";
		
		return @"DELETE FROM $table$where";
	}
	
	function renderCollection(Collection $collection) {
		if (count($collection->joins)) {
			$fragment = $this->escapeTableName($collection->name) . " AS " . $this->escapeTableName($collection->alias);
			foreach($collection->joins as $join) {
				$a2 = $this->escapeTableName($join->collection->alias);
				$fragment .= " INNER JOIN " . $this->escapeTableName($join->collection->name) . " AS $a2 ON " . $this->renderExpression($join->expression);
			}
			return $fragment;
		} else {
			if ($collection->alias)
				return $this->escapeTableName($collection->name) . " AS " . $collection->alias;
			else
				return $this->escapeTableName($collection->name);
		}
	}
	
	function renderCriteria(Criteria $criteria) {
		if (count($criteria->expressions))
			return $this->renderExpression(new expr\JunctionExpression(expr\JunctionExpression::CONJUNCTION, $criteria->expressions));
		else
			return false;
	}
	
	function renderExpression(Expression $expression) {
		if ($expression instanceof expr\ComparissionExpression)
			return $this->renderComparissionExpression($expression);
		elseif ($expression instanceof expr\ListExpression)
			return $this->renderListExpression($expression);
		elseif ($expression instanceof expr\RangeExpression)
			return $this->renderRangeExpression($expression);
		elseif ($expression instanceof expr\JunctionExpression)
			return $this->renderJunctionExpression($expression);
		elseif ($expression instanceof expr\NegationExpression)
			return $this->renderNegationExpression($expression);
		elseif ($expression instanceof expr\SqlExpression)
			return $this->renderSqlExpression($expression);
	}
	
	function renderComparissionExpression(expr\ComparissionExpression $e) {
		$a = $this->escapeFieldName($e->field);
		$o = $e->operator;
		$b = $e->valueIsField ? $this->escapeFieldName($e->value) : $this->escapeValue($e->value);
		if ($o == "=" && $b === "NULL")
			return "$a IS NULL";
		elseif ($o == "<>" && $b === "NULL")
			return "$a IS NOT NULL";
		else {
			return "$a $o $b";
		}
	}
	
	function renderListExpression(expr\ListExpression $e) {
		if (count($e->values)) {
			$self = $this;
			$f = $this->escapeFieldName($e->field);
			$v = join(", ", array_map(function($v) use($self){ return $self->escapeValue($v); }, $e->values));
			switch($e->operator) {
				case "in":
					return "$f IN ($v)"; 
				case "not-in":
					return "$f NOT IN ($v)"; 
			}
		} else
			return false;
	}
	
	function renderRangeExpression(expr\RangeExpression $e) {
		$f = $this->escapeFieldName($e->field);
		$a = $this->escapeValue($e->a);
		$b = $this->escapeValue($e->b);
		return "$f BETWEEN $a AND $b";
	}
	
	function renderJunctionExpression(expr\JunctionExpression $e) {
		if (count($e->expressions)) {
			$self = $this;
			$expressions = array_map(function($c) use ($self){ return $self->renderExpression($c); }, $e->expressions);
			$expressions = array_filter($expressions);
			$expressions = array_map(function($e){ return "($e)"; }, $expressions);
			$o = strtoupper($e->operator);
			return join(" $o ", $expressions);
		} else
			return false;
	}
	
	function renderNegationExpression(expr\NegationExpression $e) {
		$e = $this->renderExpression($e->expression);
		return "NOT ($e)";
	}

	function renderSqlExpression(expr\SqlExpression $e) {
		if ($e->parameters)
			return $this->interpolate($e->string, $e->parameters);
		else
			return $e->string;
	}
}
