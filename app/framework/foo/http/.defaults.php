<?php

use foo\core\Dispatch;
use foo\core\Request;
use foo\http\view\ViewSelector;

Request::setProfile('foo\\http\\HttpContext', 'tablet', '/ipad|gt-p/i');
Request::setProfile('foo\\http\\HttpContext', 'mobile', '/iphone|android|blackberry/i');

ViewSelector::register('header', '/^([\w-]+):(.+)$/', function(Dispatch $dispatch, $matches){
	list(, $header, $pattern) = $matches;
	return preg_match("#$pattern#", @$dispatch->request->headers[$header]);
});

ViewSelector::register('parameter', '/^([\w-]+):(.+)$/', function(Dispatch $dispatch, $matches){
	list(, $parameter, $pattern) = $matches;
	return preg_match("#$pattern#", @$dispatch->request->data[$parameter]);
});

ViewSelector::register('option', '/^([\w-]+):(.+)$/', function(Dispatch $dispatch, $matches){
	list(, $option, $pattern) = $matches;
	return preg_match("#$pattern#", @$dispatch->response->result->options[$option]);
});

ViewSelector::register('uri', function(Dispatch $dispatch, $string){
	return preg_match("#$string#", current(explode("?", $dispatch->request->uri)));
});

ViewSelector::register('extension', function(Dispatch $dispatch, $string){
	return preg_match("#\\.{$string}$#", current(explode("?", $dispatch->request->uri)));
});

ViewSelector::register('annotation', function(Dispatch $dispatch, $string){
	return $string == @$dispatch->response->result->options['viewEngine'];
});
