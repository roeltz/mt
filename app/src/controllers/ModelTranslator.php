<?php

use foo\core\Request;
use mt\output\BufferOutput;
use mt\output\FilesystemOutput;

class ModelTranslator {
	
	function help() {
		echo <<<HELP
mt: model-translator
Data model representation converter

Usage:

  php mt {importer}/{exporter} --in="input path" --out="output path"
  
Examples:

  php mt mwb/foo --in="model.mwb" --out="app/src/model"
  php mt mwb/pgsql --in="model.mwb" --out="db.sql"

Foo Model Export Parameters:
    --extend="com.example.Class|persistent|entity"
      Makes all generated classes extend some other class.
      It overrides the model's meta-attribute 'extend'.
      It expects a fully qualified class name.
      You can also use:
  	    "persistent", for the frameworks's data\\orm\\Persistent class.
  	    "entity", for the framework's entity\\core\\Entity class. 
  
PostgreSQl SQL CREATE Script Export Parameters:
    *coming soon*
    
HELP;
	}
	
	function translate(Request $request, $importer, $in, $exporter, $out) {
		$importerClass = new ReflectionClass(constant(strtoupper("MT_IMPORTER_$importer")));
		$exporterClass = new ReflectionClass(constant(strtoupper("MT_EXPORTER_$exporter")));

		$importerParameters = $this->extractParameters($importerClass, $request->data);
		$exporterParameters = $this->extractParameters($exporterClass, $request->data);
		
		$importer = $importerClass->newInstanceArgs($importerParameters);
		$model = $importer->getModel($in);
		
		$exporter = $exporterClass->newInstanceArgs($exporterParameters);
		$output = $exporter->getOutput($model);
		
		if (!$out)
			$out = $exporter->getDefaultOuputPath($in);

		$output->saveTo($out);
	}
	
	private function extractParameters(ReflectionClass $class, array $data) {
		$parameters = array();
		if ($constructor = $class->getConstructor())
			foreach($constructor->getParameters() as $parameter) {
				if (isset($data[$parameter->getName()]))
					$parameters[] = $data[$parameter->getName()];
				elseif ($parameter->isOptional())
					$parameters[] = $parameter->getDefaultValue();
			}
		return $parameters;
	}
}
