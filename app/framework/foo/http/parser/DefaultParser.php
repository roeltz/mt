<?php

namespace foo\http\parser;
use foo\http\Parser;
use foo\http\UploadedFile;

class DefaultParser implements Parser {
	
	function getData() {
			
		// parameters
		$data = $_REQUEST;
		
		// uploaded files
		foreach($_FILES as $paramName=>$file) {
			$value = null;
			
			if (is_array($file['name'])) {
				$items = array();
				foreach($file['name'] as $i=>$fileName) {
					if (!$file['error'][$i])
						$items[] = new UploadedFile($fileName, $file['tmp_name'][$i], $file['type'][$i]);
				}
				$value = $items;
			} elseif (!$file['error']) {
				$value = new UploadedFile($file['name'], $file['tmp_name'], $file['type']);
			}
			
			if ($value !== null)
				$data[$paramName] = (@$data->$paramName) ? array_merge((array) $data->$paramName, $value) : $value;
		}
		
		return $data;
	}
}
