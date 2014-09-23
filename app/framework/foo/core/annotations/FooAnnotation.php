<?php

namespace foo\core\annotations;
use Annotation;

class FooAnnotation extends Annotation {
	
	protected function checkConstraints($target) {
		foreach($this as $k=>$v) {
			$this->$k = interpolate($v, function($v){
				if (preg_match('/^%([\w-]+)%$/', $v, $m) && defined($m[1]))
					return constant($m[1]);
				else
					return "{" . $v . "}";
			});
		}
	}
}
