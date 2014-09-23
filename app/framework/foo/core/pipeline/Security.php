<?php

namespace foo\core\pipeline;
use foo\core\Dispatch;
use foo\util\Pipe;
use foo\util\Pipeline;
use foo\util\CascadingAnnotation;
use foo\core\Principal;
use foo\core\exception\SecurityException;

class Security implements Pipe {
	
	private $roles = array();
	private $reverse = array();
	
	static function add(Dispatch $dispatch) {
		$dispatch->pipeline->pipeAfter("security", "routing", $pipe = new Security());
		return $pipe;
	}

	function run(Pipeline $pipeline, $dispatch) {
		if (!$dispatch->request->session->getPrincipal())
			$dispatch->events->trigger("principal-needed");
	
		if (!$this->isAllowed($dispatch)) {
			$pipeline->finish();
			throw new SecurityException("User not allowed");
		}
	}

	function role($role, $_ = null) {
		$this->roles[$role] = array_slice(func_get_args(), 1);
		$this->reverse[$role] = array();
		$this->computeReverseRoles();
		return $this;
	}
	
	function isAllowed(Dispatch $dispatch, $allowedRoles = null) {
		$allowed = true;
		$route = $dispatch->request->route;
		$annotation = CascadingAnnotation::get($route->controller, $route->method, "foo\\core\\annotations\\req\\Secured");
		$principal = $dispatch->request->session->getPrincipal();
		$roles = array();
		
		if ($principal instanceof Principal)
			$roles = $principal->getPrincipalRoles();
		
		if ($allowedRoles) {
			$allowed = $this->isAnyRoleIncluded($roles, (array) $allowedRoles);
		} elseif ($annotation) {
			if (!$annotation->value) {
				$allowed = true;
			} else {
				$allow = ($annotation->allow == "*" && $principal) || $this->isAnyRoleIncluded($roles, (array) $annotation->allow);
				$deny = $annotation->deny == "*" || $this->isAnyRoleIncluded($roles, (array) $annotation->deny);
				$allowed = $annotation->denyFirst ? (!$deny || $allow) : ($allow && !$deny);
			}
		}
		return $allowed;
	}
	
	private function isAnyRoleIncluded(array $concrete, array $annotated) {
		foreach($concrete as $c) {
			$pattern = "/^$c\$/";
			foreach($annotated as $a)
				if (preg_match($pattern, $a))
					return true;
		}
		foreach($annotated as $a) {
			foreach((array) @$this->reverse[$a] as $r) {
				$pattern = "/^$r\$/";
				foreach($concrete as $c) {
					if (preg_match($pattern, $c))
						return true;
				}
			}
		}
	}

	private function computeReverseRoles() {
			
		foreach(array_keys($this->roles) as $role) {
			$stack = array($role);
			while($stack) {
				$item = array_shift($stack);
				foreach($this->roles as $r=>$rs) {
					if (in_array($item, $rs)) {
						if (!in_array($r, $this->reverse[$role])) {
							$this->reverse[$role][] = $r;
							$stack[] = $r;
						}
					}
				}
			}
		}

		foreach($this->reverse as $r=>$rs)
			foreach($rs as $rr)
				$this->reverse[$r] = array_unique(array_merge($this->reverse[$r], $this->reverse[$rr]));
	}
}
