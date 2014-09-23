<?php

namespace mt\exporter\pipa;
use mt\util\php\Method;
use mt\util\php\Parameter;
use mt\util\php\PHPClass;
use mt\util\php\Property;

class ComputedMethod extends Method {

	function __construct(Property $property) {
		$name = "get".ucfirst($property->name);
		parent::__construct($name, "public");
	}

	function populate() {
		$this->addBodyLine("// TODO Computed method {$this->type->name}::{$this->name}()");
	}
}
