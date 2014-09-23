<?php

namespace mt\exporter\pipa;
use mt\util\php\Method;
use mt\util\php\Parameter;
use mt\util\php\PHPClass;

class CreateMethod extends Method {
	public $protoparameters;

	function __construct(array $parameters) {
		parent::__construct("create", "public", true);
		$this->protoparameters = $parameters;
	}

	function populate() {
		$class = $this->type;
		$instanceVar = lcfirst($class->name);
		$this->addBodyLine("\$$instanceVar = new self;");
		foreach($this->protoparameters as $parameter) {
			if ($parameter->typeHint)
				$this->dependencies->add($parameter->typeHint);
			if ($parameter->hasDefaultValue && is_array($parameter->defaultValue) && isset($parameter->defaultValue['assign'])) {
				$this->addBodyLine("\$$instanceVar->{$parameter->name} = {$parameter->defaultValue['assign']};");
			} else {
				$this->addParameter($parameter);
				$this->addBodyLine("\$$instanceVar->{$parameter->name} = \${$parameter->name};");
			}
		}
		$this->addBodyLine("\${$instanceVar}->save();");
		$this->addBodyLine("return \$$instanceVar;");
	}
}
