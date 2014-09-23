<?php

namespace foo\core;
use foo\util\EventSource;

abstract class Session {
	
	private $principal;
	
	abstract function get($key);
	abstract function set($key, &$value);
	abstract function remove($key);
	abstract function destroy();
	
	function setLifetime($seconds) {}
	
	function getPrincipal() {
		if (!$this->principal)
			$this->principal = $this->get(CFG_SESSION_PRINCIPAL_KEY);
		return $this->principal;
	}
	
	function setPrincipal($principal) {
		$this->principal = $principal;
		$this->set(CFG_SESSION_PRINCIPAL_KEY, $principal);
	}
	
	function unsetPrincipal() {
		$this->principal = null;
		$this->remove(CFG_SESSION_PRINCIPAL_KEY);
	}
	
}
