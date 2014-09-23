<?php

namespace foo\cli;

class CliInput implements \foo\core\Input {
	
	private $uri = "";
	private $data = array();
	
	function __construct() {
		global $argv;
		$this->parseArgv($argv);
	}
	
	function getURI() {
		return $this->uri;
	}
	
	function getSource() {
		return "CLI";
	}
	
	function getData() {
		return $this->data;
	}
	
	function getHeaders() {
		return null;
	}
	
	function getUserAgent() {
		return PHP_OS;
	}

	private function parseArgv($argv) {
		$argv = array_slice($argv, array_search($_SERVER['PHP_SELF'], $argv) + 1);
		foreach($argv as $arg) {
			if (preg_match('/^--?([\w-]+)$/', $arg, $m))
				$this->data[$m[1]] = true;
			elseif (preg_match('/^--?([\w-]+)=(.+)$/', $arg, $m))
				$this->data[$m[1]] = $m[2];
			else
				$this->uri = $arg;
		}
	}

	
}
