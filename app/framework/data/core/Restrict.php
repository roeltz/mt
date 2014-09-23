<?php

namespace data\core;
use data\core\expression\ComparissionExpression;
use data\core\expression\RangeExpression;
use data\core\expression\ListExpression;
use data\core\expression\JunctionExpression;
use data\core\expression\NegationExpression;
use data\core\expression\SqlExpression;

abstract class Restrict {
	
	static function eq($field, $value) {
		return new ComparissionExpression($field, "=", $value);
	}

	static function eqAll(array $values) {
		$expressions = array();
		foreach($values as $field=>$value)
			$expressions[] = self::eq($field, $value);
		return self::conj($expressions);
	}

	static function eqf($field, $value) {
		return new ComparissionExpression($field, "=", $value, true);
	}

	static function eqfAll(array $fields) {
		$expressions = array();
		foreach($fields as $field=>$value)
			$expressions[] = self::eqf($field, $value);
		return self::conj($expressions);
	}

	static function ne($field, $value) {
		return new ComparissionExpression($field, "<>", $value);
	}

	static function nef($field, $value) {
		return new ComparissionExpression($field, "<>", $value, true);
	}
	
	static function gt($field, $value) {
		return new ComparissionExpression($field, ">", $value);
	}

	static function gtf($field, $value) {
		return new ComparissionExpression($field, ">", $value, true);
	}
	
	static function ge($field, $value) {
		return new ComparissionExpression($field, ">=", $value);
	}

	static function gef($field, $value) {
		return new ComparissionExpression($field, ">=", $value, true);
	}
	
	static function lt($field, $value) {
		return new ComparissionExpression($field, "<", $value);
	}

	static function ltf($field, $value) {
		return new ComparissionExpression($field, "<", $value, true);
	}
	
	static function le($field, $value) {
		return new ComparissionExpression($field, "<=", $value);
	}

	static function lef($field, $value) {
		return new ComparissionExpression($field, "<=", $value, true);
	}
	
	static function like($field, $value) {
		return new ComparissionExpression($field, "like", $value);
	}
	
	static function regexp($field, $value) {
		return new ComparissionExpression($field, "regexp", $value);
	}
	
	static function between($field, $a, $b) {
		return new RangeExpression($field, $a, $b);
	}
	
	static function in($field, array $values) {
		return new ListExpression($field, "in", $values);
	}
	
	static function nin($field, array $values) {
		return new ListExpression($field, "not-in", $values);
	}
	
	static function conj($_) {
		return new JunctionExpression(JunctionExpression::CONJUNCTION, array_flatten(func_get_args()));
	}

	static function disj($_) {
		return new JunctionExpression(JunctionExpression::DISJUNCTION, array_flatten(func_get_args()));
	}

	static function not(Expression $expression) {
		return new NegationExpression($expression);
	}
	
	static function sql($string, array $parameters = null) {
		return new SqlExpression($string, $parameters);
	}
}
