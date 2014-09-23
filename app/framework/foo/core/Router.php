<?php

namespace foo\core;

interface Router {
	function resolve(Dispatch $dispatch);
}
