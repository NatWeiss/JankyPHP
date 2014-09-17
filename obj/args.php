<?php

class args{
	function __construct($ra = array()){
		$this->ra = $ra;
		}
	
	function reference(&$ra){
		$this->ra =& $ra;
		}
	
	function getRa(){
		return $this->ra;
		}

	function get(){
		$args = ((func_num_args()==1 and is_array(func_get_arg(0))) ? func_get_arg(0) : func_get_args());
		
		// return val
		$ref =& $this->ra;
		foreach($args as $k)
			$ref =& $ref[$k];
		return $ref;
		}

	function set(){
		// last arg is value
		$args = ((func_num_args()==1 and is_array(func_get_arg(0))) ? func_get_arg(0) : func_get_args());
		$val = array_pop($args);

		// assign array of values
		if(count($args) == 0
		and is_array($val)){
			foreach($val as $k => $i)
				$this->ra[$k] = $i;
			}
		// assign val
		else{
			$ref =& $this->ra;
			foreach($args as $k)
				$ref =& $ref[$k];
			$ref = $val;
			}
		
		return $val;
		}

	function clear(){
		$args = ((func_num_args()==1 and is_array(func_get_arg(0))) ? func_get_arg(0) : func_get_args());

		// create unset command
		$cmd = '';
		foreach($args as $k)
			$cmd .= '['.(is_string($k) ? "'$k'" : $k).']';
		$cmd = 'unset($this->ra'.$cmd.');';

		eval($cmd);
		}

	function append(){
		$args = ((func_num_args()==1 and is_array(func_get_arg(0))) ? func_get_arg(0) : func_get_args());
		$val = array_pop($args);
		
		// assign val
		$ref =& $this->ra;
		foreach($args as $k)
			$ref =& $ref[$k];
		$ref .= $val;
		}

	function has(){
		$args = ((func_num_args()==1 and is_array(func_get_arg(0))) ? func_get_arg(0) : func_get_args());

		// traverse and test isset
		$ref =& $this->ra;
		foreach($args as $k)
			$ref =& $ref[$k];
		
		// has this val?
		$ret = isset($ref[$k]);
		return $ret;
		}
	}

?>
