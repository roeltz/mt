<?php

use foo\core\locale\Locale;

Locale::registerResourceClass("foo\\core\\locale\\resource\\MoResource", function($path){
	return preg_match('/\\.mo$/i', $path);
});
