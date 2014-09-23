<?php

namespace foo\http\view\filter;
use foo\core\view\Filter;

class PartialsFilter implements Filter {
	
	function process($buffer) {
		$partials = array();
		
		preg_match_all('/<!--\\s*([\w-]+)\\s+block\\s*-->([\s\S]*)<!--\\s*end\\s+\\1\\s+block\\s*-->/i', $buffer, $blocks);
		
		foreach($blocks[1] as $i=>$block)
			$partials[$block] = $blocks[2][$i];
		
		$buffer = preg_replace_callback('/<!--\\s*([\w-]+)\\s+content\\s*-->\\s*([\s\S]*?)\\s*<!--\\s*end\\s+\\1\\s+content\\s*-->(?:\\s*\\n)?/i', function($m) use(&$partials){
			$block = $m[1];
			$content = $m[2];
			$content = preg_replace('/<!--\\s*parent\\s*-->/i', @$partials[$block], $content);
			$partials[$block] = $content;
		}, $buffer);

		$buffer = preg_replace_callback('/<!--\\s*([\w-]+)\\s+block\\s*-->([\s\S]*)<!--\\s*end\\s+\\1\\s+block\\s*-->(?:\\s*\\n)?/i', function($m) use(&$partials){
			$block = $m[1];
			return @$partials[$block];
		}, $buffer);

		$buffer = preg_replace_callback('/<!--\\s*([\w-]+)\\s+placeholder\\s*-->/i', function($m) use(&$partials){
			$block = $m[1];
			return @$partials[$block];
		}, $buffer);
		
		return $buffer;
	}
}
