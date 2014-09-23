<?php

namespace foo\http\locale;
use foo\core\Dispatch;
use foo\core\locale\LocaleExtractor;
use foo\core\locale\Locale;

class HeaderLocaleExtractor implements LocaleExtractor {
	
	function getLocale(Dispatch $dispatch) {
		if ($header = $dispatch->request->headers['Accept-Language']) {
			preg_match_all($this->getRegex(), $header, $matches);
			if ($matches)
				return new Locale($matches[0][0]);
		}
	}
	
	private function getRegex() {
		$regex = join("|", Locale::accepted());
		return "/$regex/i";
	}
}
