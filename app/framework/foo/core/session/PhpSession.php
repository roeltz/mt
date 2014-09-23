<?php

namespace foo\core\session;
use foo\core\Session;

class PhpSession extends Session {
	
	public $id;
	private $set = false;
	
	function __construct($id = null) {
		if ($id) {
			$this->id = $id;
			$this->set = true;
		} else {
			$this->id = session_id();
		}
	}

    function set($key, &$value) {
    	$this->setSession();
        $_SESSION[$key] = $value;
    }

    function get($key) {
    	$this->setSession();
        return @$_SESSION[$key];
    }
    
    function remove($key) {
    	$this->setSession();
        unset($_SESSION[$key]);
    }

    function destroy() {
    	$this->setSession();
        session_destroy();
		$_SESSION = array();
    }
	
	function setLifetime($seconds) {
		session_set_cookie_params($seconds);
		session_write_close();
		session_id($this->id);
		@session_start();
	}
	
	private function setSession() {
    	if ($this->set && session_id() != $this->id) {
    		session_write_close();
    		session_id($this->id);
		}		
   		@session_start();
	}
}
