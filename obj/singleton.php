<?php

class singleton{
	// our single object
	static protected $o = null;

	// get our object
	protected static function getInstance($class = __CLASS__){
		if(is_null(self::$o)){
			self::$o = true; // in case new fails
			self::$o = new $class;
			}

		return self::$o;
		}

	/*
	// so that our object is of the child class,
	// getInstance can be overriden like this:
	static function getInstance(){
		return parent::getInstance(__CLASS__);
		}
	*/
	}

?>
