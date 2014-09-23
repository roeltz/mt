<?php

namespace foo\util;
use foo\util\cache\Cache;

class Logger {
	
	const OFF = 7;
	const FATAL = 6;
	const ERROR = 5;
	const WARNING = 4;
	const INFO = 3;
	const DEBUG = 2;
	const PROFILE = 1;
	const TRACE = 0;
	
	static private $levelLabels = array("trace", "profile", "debug", "info", "warning", "error", "fatal", "off");
	
	public $id;
	public $level;
	public $path;
	public $format;
	
	static function get($id) {
		return Cache::get("memory")->get("foo\\util\\Logger", $id);
	}
	
	static function set($id, $level = Logger::ERROR, $path = "app/log/{id}-{year}{month}.log", $format = "[{datetime} {level}] {message}\n") {
		return Cache::get("memory")->set("foo\\util\\Logger", $id, new Logger($id, $level, $path, $format));
	}
	
	function __construct($id, $level, $path, $format) {
		$this->id = $id;
		$this->level = $level;
		$this->path = $path;
		$this->format = $format;
	}
	
	function log($message, $level = Logger::INFO) {
		if ($this->isLevel($level))
			$this->output($message, $level);
	}
	
	function output($message, $level) {
		file_put_contents($this->getPath($level), $this->getString($message, $level), FILE_APPEND);
	}

	function trace($message) {
		$this->log($message, Logger::TRACE);
	}
	
	function debug($message) {
		$this->log($message, Logger::DEBUG);
	}
	
	function info($message) {
		$this->log($message, Logger::INFO);
	}

	function warning($message) {
		$this->log($message, Logger::WARNING);
	}

	function error($message) {
		$this->log($message, Logger::ERROR);
	}

	function fatal($message) {
		$this->log($message, Logger::FATAL);
	}
	
	function isLevel($level) {
		return $level >= $this->level;
	}
	
	protected function getPath($level) {
		return fill($this->path, array(
			'id'=>$this->id,
			'year'=>date('Y'),
			'month'=>date('m'),
			'day'=>date('d'),
			'hour'=>date('H'),
			'minute'=>date('i'),
			'second'=>date('s'),
			'datetime'=>date('Y-m-d H:i:s'),
			'date'=>date('Y-m-d'),
			'time'=>date('H:i:s'),
			'level'=>self::$levelLabels[$level]
		));
	}
	
	protected function getString($message, $level) {
		return fill($this->format, array(
			'id'=>$this->id,
			'year'=>date('Y'),
			'month'=>date('m'),
			'day'=>date('d'),
			'hour'=>date('H'),
			'minute'=>date('i'),
			'second'=>date('s'),
			'datetime'=>date('Y-m-d H:i:s'),
			'date'=>date('Y-m-d'),
			'time'=>date('H:i:s'),
			'message'=>$message,
			'level'=>self::$levelLabels[$level]
		));
	}
}
