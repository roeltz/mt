<?php

namespace foo\util;
use Exception;

class Pipeline {
	
	private $lastException;
	private $pipes = array();
	private $sequence = array();
	private $currentSequenceIndex = 0;
	private $active = true;
	private $lastPipe;
	private $lastContext;
	private $lastResult = null;
	
	function __construct(array $pipes = null) {
		if ($pipes)
			foreach($pipes as $id=>$pipe)
				$this->appendPipe($id, $pipe);
	}
	
	function step($context) {
		try {
			$pipeId = $this->sequence[$this->currentSequenceIndex++];
			$this->lastResult = null;
			if ($pipe = @$this->pipes[$pipeId]) {
				$this->lastPipe = $pipeId;
				$this->lastContext = $context;
				$this->lastResult = $pipe->run($this, $context);
			} else
				$this->active = false;
			return true;
		} catch(Exception $e) {
			$this->lastException = $e;
			return false;
		}
	}
	
	function isActive() {
		return $this->active && $this->currentSequenceIndex < count($this->sequence);
	}
	
	function getCurrentPipe() {
		return @$this->sequence[$this->currentSequenceIndex];
	}

	function getLastPipe() {
		return $this->lastPipe;
	}
	
	function getPipe($id) {
		return @$this->pipes[$id];
	}
	
	function getLastException() {
		return $this->lastException;
	}
	
	function getLastResult() {
		return $this->lastResult;
	}
	
	function appendPipe($id, $pipe) {
		if ($this->getPipe($id))
			$this->removePipe($id);
		$this->pipes[$id] = $pipe;
		$this->sequence[] = $id;
		return $this;
	}
	
	function prependPipe($id, $pipe) {
		if ($this->getPipe($id))
			$this->removePipe($id);
		$this->pipes[$id] = $pipe;
		array_unshift($this->sequence, $id);
		return $this;		
	}

	function pipeBefore($id, $ref, $pipe) {
		if ($this->getPipe($id))
			$this->removePipe($id);
		$this->pipes[$id] = $pipe;
		array_splice($this->sequence, array_search($ref, $this->sequence) - 1, 0, array($id));
		return $this;
	}

	function pipeAfter($id, $ref, $pipe) {
		if ($this->getPipe($id))
			$this->removePipe($id);
		$this->pipes[$id] = $pipe;
		array_splice($this->sequence, array_search($ref, $this->sequence) + 1, 0, array($id));
		return $this;
	}
	
	function pipe($expression, Pipe $pipe) {
		if (preg_match('/^\w+$/', $expression, $m))
			$this->appendPipe($m[0], $pipe);
		if (preg_match('/^-(\w+)$/', $expression, $m))
			$this->prependPipe($m[1], $pipe);
		elseif (preg_match('/^(\w+)>(\w+)$/', $expression, $m))
			$this->pipeAfter($m[1], $m[2], $pipe);
		elseif (preg_match('/^(\w+)<(\w+)$/', $expression, $m))
			$this->pipeBefore($m[1], $m[2], $pipe);		
		return $this;
	}
	
	function removePipe($id) {
		array_remove($this->sequence, $id);
		unset($this->pipes[$id]);
		return $this;
	}
	
	function jumpTo($id) {
		$this->active = true;
		$this->currentSequenceIndex = array_search($id, $this->sequence) - 1;
		return $this;
	}
	
	function repeatLast() {
		$this->jumpTo($this->lastPipe);
		return $this->step($this->lastContext);
	}
	
	function finish() {
		$this->active = false;
		return $this;
	}
}
