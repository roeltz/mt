<?php

namespace foo\http\view\filter;
use foo\core\view\Filter;

class TidyFilter implements Filter {
	
	function process($buffer) {
		$config = array(
			'indent'=> true,
			'vertical-space'=>true,
			'new-blocklevel-tags'=>'article,aside,canvas,figcaption,figure,footer,header,hgroup,output,progress,section,video',
			'new-inline-tags'=>'audio,details,command,datalist,mark,meter,nav,source,summary,time',
			'wrap'=> '100'
		);
		
		$tidy = new \tidy();
		$tidy->parseString($buffer, $config, 'utf8');
		$tidy->cleanRepair();

		return tidy_get_output($tidy);
	}
}