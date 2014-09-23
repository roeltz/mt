<?php

namespace data\orm\exception;

class InstanceNotFoundException extends ORMException {
	
	function __construct($class, array $pk) {
		$pk = preg_replace('/^\\{|\\}$/', '', preg_replace('/"([^"]+)":/', '$1:', json_encode($pk)));
		parent::__construct("Instance not found for class $class($pk)");
	}
}
