<?php

namespace data\sources\mongo;
use data\core\CriteriaDecorator;

class MongoDBCriteria extends CriteriaDecorator {
	
	function mapReduce($map, $reduce, array $scope = null) {
		return $this->criteria->dataSource->mapReduce($this->criteria, $map, $reduce, $scope);
	}
}
