<?php

use foo\core\locale\Locale;

function __($message, $parameters = array(), $domain = "default") {
	if ($locale = Locale::get()) {
		return fill($locale->translate($message, $domain), $parameters);
	} else {
		return fill($message, $parameters);
	}
}
