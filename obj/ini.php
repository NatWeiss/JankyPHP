<?php

class ini extends singleton{
	// as of php 5.3, you can remove all the get, set, etc..
	/*
	static function __callStatic($func,$args){
		self::call($func,$args);
		}
	*/
	private static function call($func,$args){
		$self = self::getInstance();
		$a = new args();
		$a->reference($self->ra);
		return $a->$func($args);
		}

	static function get(){
		$args = func_get_args();
		return self::call(__FUNCTION__,$args);
		}

	static function set(){
		$args = func_get_args();
		return self::call(__FUNCTION__,$args);
		}

	static function clear(){
		$args = func_get_args();
		return self::call(__FUNCTION__,$args);
		}

	static function has(){
		$args = func_get_args();
		return self::call(__FUNCTION__,$args);
		}

	static function append(){
		$args = func_get_args();
		return self::call(__FUNCTION__,$args);
		}
	}

?>
