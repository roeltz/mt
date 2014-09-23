<?php

namespace mt\exporter\pipa;
use mt\util\php\Method;
use mt\util\php\Parameter;
use mt\util\php\PHPClass;

class EditMethod extends Method {
	public $protoparameters;

	function __construct(array $parameters) {
		parent::__construct("edit", "public");
		$this->protoparameters = $parameters;
	}

	function populate() {
		$class = $this->type;
		foreach($this->protoparameters as $parameter) {
			if ($parameter->typeHint)
				$this->dependencies->add($parameter->typeHint);
			if ($parameter->hasDefaultValue && is_array($parameter->defaultValue) && isset($parameter->defaultValue['assign'])) {
				$this->addBodyLine("\$this->{$parameter->name} = {$parameter->defaultValue['assign']};");
			} else {
				$this->addParameter($parameter);
				$this->addBodyLine("\$this->{$parameter->name} = \${$parameter->name};");
			}
		}
		$this->addBodyLine("\$this->update();");
		$this->addBodyLine("return \$this;");
	}
}
