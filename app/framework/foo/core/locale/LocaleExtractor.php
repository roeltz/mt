<?php

namespace foo\core\locale;
use foo\core\Dispatch;

interface LocaleExtractor {
	function getLocale(Dispatch $dispatch);
}
