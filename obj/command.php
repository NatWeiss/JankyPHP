<?php

class command{
	function __construct($argv){
		$this->parse($argv);
		}

	function parse($argv){
		foreach($argv as $k => $arg){
			$arg = trim($arg);
			
			// is option?
			if('-' == $arg[0]){
				// expand option like "--dir=glom" or "--quiet"
				list($key,$val) = explode('=',
					substr($arg,$arg[1] == '-' ? 2 : 1));
				if(!strlen($val))
					$val = 1;
				
				$this->opts[$key] = $val;
				}
			
			// is part of command line
			else{
				// command file
				if($k == 0
				// you want to pass me just args as argv? why dont you push on a cmdFile?
				//and ($arg[0] == '/' or substr($arg,0,2) == './')
				and is_file($arg))
					$this->cmdFile = basename($arg);
				
				// arguments
				else
					$this->args[] = $arg;
				}
			}
		}

	function getArg($i){
		return $this->args[$i];
		}

	function getOpt($i){
		return $this->opts[$i];
		}

	function getName(){
		return $this->cmdFile;
		}

	static function drawControlCode($str = '0m'){
		return "\033[${str}";
		}

	static function drawBanner($str,$echo = false){
		$ret = self::drawControlCode('33;1;41m').$str.
			self::drawControlCode('K').self::drawControlCode();
		
		if($echo)
			echo "$ret\n";
		return $ret;
		}

	static function drawProgressBar($percent){
		static $i = 0;
		if($percent == 'reset'){
			$i = 0;
			return;
			}
		$max = 80;
	
		// draw up to percent
		$ret = '';
		$new = (int)(clamp(round($percent,1),0.0,1.0) * 80);
		while($i <= $new){
			if($i == 0)
				$ret .= '[';
			elseif($i == $max)
				$ret .= "]\n";
			elseif(($i % ($max / 4)) == (($max / 4) - 1))
				$ret .= '|';
			else
				$ret .= '=';
			$i++;
			}
		
		return $ret;
		}

	static function clearScreen(){
		echo self::drawControlCode('2J').self::drawControlCode().
			self::drawControlCode('H').self::drawControlCode();
		}
	}

?>
