<?php

namespace foo\http;
use foo\util\File;

class UploadedFile extends File {
	public $originalName;
	
	function __construct($originalName, $tmpName, $type) {
		parent::__construct($tmpName, $type);
		$this->originalName = $originalName;
	}

	function getOriginalNameExtensionless() {
		$parts = explode(".", $this->originalName);
		array_pop($parts);
		return join(".", $parts);
	}
	
	function getOriginalExtension() {
		return end(explode(".", $this->originalName));
	}
	
	function move($path) {
		move_uploaded_file($this->path, $path);
		return new File($path);
	}
}
