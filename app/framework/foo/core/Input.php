<?php

namespace foo\core;

interface Input {
	function getURI();
	function getSource();
	function getData();
	function getHeaders();
	function getUserAgent();	
}
