<?php

namespace foo\core\locale;
use foo\core\Dispatch;
use foo\core\Route;
use foo\util\Pipe;
use foo\util\Pipeline;

class Localization implements Pipe {
		
	private $extractors = array();

	static function add(Dispatch $dispatch) {
		$dispatch->pipeline->prependPipe("localization", $pipe = new Localization());
		return $pipe;
	}
	
	function accept($_) {
		Locale::accepted(func_get_args());
		return $this;
	}
	
	function extractor(LocaleExtractor $extractor) {
		$this->extractors[] = $extractor;
		return $this;
	}
	
	function resource(Resource $resource, $domain = "default") {
		Locale::registerResource($resource, $domain);
		return $this;
	}
	
	function run(Pipeline $pipeline, $dispatch) {
		foreach($this->extractors as $extractor)
			if ($locale = $extractor->getLocale($dispatch)) {
				$locale->setEnvironment();
				return;
			}
	}
}
