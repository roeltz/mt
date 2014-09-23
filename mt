<?php

require_once "app/environment.php";

use foo\cli\CliContext;
use foo\core\router\PatternRouter;

CliContext::hook("cli-error");

CliContext::cliDispatch(new PatternRouter(array(
	'/'=>'ModelTranslator:help',
	'{importer}/{exporter}'=>'ModelTranslator:translate'
)));