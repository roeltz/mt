<?php

namespace foo\util;

class File {
	
	public $path;
	public $type;
	
	function __construct($path, $type = "application/octet-stream") {
		$this->path = $path;
		$this->type = $type;
	}
	
	function getNameExtensionless() {
		$parts = explode(".", basename($this->path));
		array_pop($parts);
		return join(".", $parts);
	}
	
	function getExtension() {
		return end(explode(".", basename($this->path)));
	}
	
	function getSize() {
		return filesize($this->path);
	}
	
	function delete() {
		unlink($this->path);
	}	
}
