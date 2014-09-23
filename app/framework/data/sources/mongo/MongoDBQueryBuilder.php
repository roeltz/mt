<?php

namespace data\sources\mongo;
use data\core\DataSource;
use data\core\Criteria;
use data\core\Expression;
use data\core\Restrict;
use data\core\expression as expr;
use DateTime;
use MongoId;
use MongoDate;
use MongoDBRef;
use MongoRegexp;
use MongoCode;

class MongoDBQueryBuilder {
	
	private $dataSource;
	
	function __construct(DataSource $dataSource) {
		$this->dataSource = $dataSource;
	}

	function build(Criteria $criteria) {
		if (count($criteria->expressions))
			return $this->renderExpression(Restrict::conj($criteria->expressions));
		else
			return array();
	}
	
	function buildSort(Criteria $criteria) {
		$orders = array();
		foreach($criteria->orders as $order)
			$orders[$order->field] = $order->asc ? 1 : -1;
		return $orders;
	}
	
	function deprivatizeValues(&$values) {
		object_walk_recursive(function(&$value){
			if (is_object($value)
				&& !($value instanceof DateTime)) {
				$value2 = new stdClass;
				foreach($value as $k=>$v)
					$value2->$k = $v;
				$value = $value2;
			}
		}, $values);
		return $values;
	}
	
	function resolveExpressionOperator($field, $value, $operator) {

		if ($field == "_id" && strlen($value) == 24 && ctype_xdigit($value))
			$value = new MongoId($value);
		else
			$value = $this->dataSource->escape($value);
		
		switch($operator) {
			case '=':
				if ($value === null)
					return array($field=>array('$type'=>10));
				else
					return array($field=>$this->dataSource->escape($value));
			case 'like':
				$value = preg_replace('/[()[\]\\.*?|]/', '\\\\$1', $value);
				$value = str_replace('%', '.*', $value);
				$value = str_replace('_', '.', $value);
				$value = "/$value/m";
			case 'regexp':
				return array($field=>array('$regex'=>new MongoRegex($value)));
		}
		
		$equiv = array('='=>'$eq', '<>'=>'$ne',
						'<'=>'$lt', '>'=>'$gt', '<='=>'$lte', '>='=>'$gte',
						'is-null'=>'$eq', 'is-not-null'=>'$ne');

		return array($field=>array($equiv[$operator]=>$value));
	}
	
	function renderComparissionExpression(expr\ComparissionExpression $expression) {
		return $this->resolveExpressionOperator($expression->field, $expression->value, $expression->operator);
	}
	
	function renderRangeExpression(expr\RangeExpression $expression) {
		return array($expression->field => array('$gte'=>$this->dataSource->escape($expression->a), '$lte'=>$this->dataSource->escape($expression->b)));
	}

	function renderListExpression(expr\ListExpression $expression) {
		$equiv = array('in'=>'$in', 'not-in'=>'$nin');
		$values = array();
		foreach($expression->values as $v)
			$values[] = $this->dataSource->escape($v);
		return array($expression->field => array($equiv[$expression->operator]=>$values));
	}
	
	function renderNegationExpression(expr\NegationExpression $expression) {
		$criteria = $this->renderExpression($expression);
		return array('$not'=>$criteria);		
	}
	
	function renderJunctionExpression(expr\JunctionExpression $expression) {
		$criteria = array();
		if ($expression->operator == expr\JunctionExpression::CONJUNCTION) {
			foreach($expression->expressions as $e) {
				$criteria = array_merge_recursive($criteria, $this->renderExpression($e));
			}
		} else {
			$criteria['$or'] = array();
			foreach($expression->expressions as $e) {
				$criteria['$or'][] = $this->renderExpression($e);
			}
		}
		return $criteria;
	}
	
	function renderExpression(Expression $expression) {
		if ($expression instanceof expr\ComparissionExpression) {
			return $this->renderComparissionExpression($expression);
		} elseif ($expression instanceof expr\RangeExpression) {
			return $this->renderRangeExpression($expression);
		} elseif ($expression instanceof expr\ListExpression) {
			return $this->renderListExpression($expression);
		} elseif ($expression instanceof expr\NegationExpression) {
			return $this->renderNegationExpression($expression);
		} elseif ($expression instanceof expr\JunctionExpression) {
			return $this->renderJunctionExpression($expression);
		}
	}

}
