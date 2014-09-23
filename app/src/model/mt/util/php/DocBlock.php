<?php

namespace mt\util\php;

class DocBlock {
	public $comments;
	public $annotations = array();
	
	function addAnnotation(Annotation $annotation, $unique = false) {
		if ($unique)
			foreach($this->annotations as $a)
				if ($a->class == $annotation->class)
					return;
		
		$this->annotations[] = $annotation;
	}
	
	function getAnnotation($class) {
		foreach($this->annotations as $annotation)
			if ($annotation->class == $class)
				return $annotation;
	}
	
	function removeAnnotation($class) {
		$pattern = '/^' . str_replace("\\*", "", preg_quote($class)) . '/i';
		foreach($this->annotations as $i=>$annotation)
			if (preg_match($pattern, $annotation->class))
				unset($this->annotations[$i]);
	}
}
