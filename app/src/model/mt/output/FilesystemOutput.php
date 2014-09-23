<?php

namespace mt\output;
use mt\output;

class FilesystemOutput implements Output {
	
	private $files = array();
	
	function addFile($path, $content) {
		$this->files[$path] = $content;
		return $this;
	}
		
	function getData() {
		return $this->files;
	}
	
	function doStandardOutput() {
		foreach($this->files as $filePath=>$content)
			echo "### $filePath\n\n$content\n\n";
		return $this;
	}
	
	function saveTo($path) {
		foreach($this->files as $filePath=>$content) {
			if (!file_exists($dir = dirname("$path/$filePath")))
				mkdir($dir, 0777, true);
			
			file_put_contents("$path/$filePath", $content);
		}
		return $this;
	}
}
