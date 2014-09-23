<?php

namespace data\util;
use data\core\Expression;
use data\core\expression as exp;

class GenericCriteriaEvaluator {
	
	function evaluate(&$values, Expression $expression) {
		if ($expression instanceof exp\JunctionExpression)
			return $this->evaluateJunctionExpression($values, $expression);
		elseif ($expression instanceof exp\NegationExpression)
			return $this->evaluateNegationExpression($values, $expression);
		elseif ($expression instanceof exp\ComparissionExpression)
			return $this->evaluateComparissionExpression($values, $expression);
		elseif ($expression instanceof exp\RangeExpression)
			return $this->evaluateRangeExpression($values, $expression);
		elseif ($expression instanceof exp\ListExpression)
			return $this->evaluateListExpression($values, $expression);
	}
	
	function evaluateJunctionExpression(&$values, exp\JunctionExpression $junction) {
		$true = true;
		if ($junction->expressions) {
			$true = $isConjunction = $junction->operator == exp\JunctionExpression::CONJUNCTION;
			
			foreach($junction->expressions as $i=>$expression) {
				if ($isConjunction) {
					$true = $true && $this->evaluate($values, $expression);
					if (!$true) return false;
				} else {
					$true = $true || $this->evaluate($values, $expression);
					if ($true) return true;
				}
			}
		}
		return $true;
	}
	
	function evaluateNegationExpression(&$values, exp\NegationExpression $negation) {
		return !$this->evaluate($values, $negation);
	}
	
	function evaluateComparissionExpression(&$values, exp\ComparissionExpression $comparission) {
		$a = $values[$comparission->field];
		$b = $comparission->valueIsField ? $values[$comparission->value] : $comparission->value;
		
		switch($comparission->operator) {
			case "=":
				return $a == $b;
			case "<>":
				return $a != $b;
			case ">":
				return $a > $b;
			case "<":
				return $a < $b;
			case ">=":
				return $a >= $b;
			case "<=":
				return $a <= $b;
			case "like":
				$b = preg_replace('/([()[\]\\.*?|])/', '\\\\$1', $b);
				$b = str_replace('%', '.*', $b);
				$b = str_replace('_', '.', $b);
				$b = "/$b/m";
				return preg_match($b, $a);
			case "regexp":
				return preg_match($b, $a);
			default:
				return false;
		}
	}
	
	function evaluateRangeExpression(&$values, exp\RangeExpression $range) {
		$value = $values[$range->field];
		return ($value >= $range->a) && ($value <= $range->b);
	}
	
	function evaluateListExpression(&$values, exp\ListExpression $list) {
		$found = false;
		$value = $values[$list->field];
		foreach($list->values as $v)
			if ($v == $value) {
				$found = true;
				break;
			}
		return !($found xor ($list->operator == exp\ListExpression::IN));
	}
}
