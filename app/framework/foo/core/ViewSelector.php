<?php

namespace foo\core;

interface ViewSelector {
	function resolve(Dispatch $dispatch);
}
