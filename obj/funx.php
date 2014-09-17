<?php

//
// universal, site independent, de-coupled static functions
//
class funx{
	static function isCommandline(){
		return (php_sapi_name() == 'cli');
		}

	// display usage and die
	static function usage($msg,$ret = 1){
		echo $msg;
		die($ret);
		}

	// include a template file with arguments
	static function inc($fname,$vars = null){
		$ret = false;
		
		// try kit directory?
		if(!is_file($fname))
			$fname = DIR_KIT.$fname;
	
		if(is_file($fname)){
			ob_start();
			
			// include arguments as passed in array
			$args = func_get_args();
			if(!is_null($vars) and is_array($vars) and count($args) <= 2){
				foreach($vars as $k => $i)
					$$k = $i;
				}
			
			// include all arguments as var1, var2, var3
			elseif(count($args) >= 2){
				array_shift($args);
				$cnt = 1;
				foreach($args as $k => $i){
					$name = 'var'.$cnt;
					$$name = $i;
					$cnt++;
					}
				}
			include $fname;
			$ret = ob_get_contents();
			ob_end_clean();
			}
	
		return $ret;
		}

	// echo and send txt to debug log
	static function decho($txt,$append = "\n"){
		// self::debug($txt);
		if(ini::get('silent'))
			return;

		// draw
		if(self::isCommandline())
			$out = str::mixed2str($txt).$append;
		else
			$out = '<pre>'.str::text2html(str::mixed2str($txt)).'</pre>';
		
		// just return
		if($append === false)
			return $out;
		
		echo $out;
		}

	// send text to debug log
	static function debug($txt,$log = ''){
		// set debug log?
		if(!defined('DEBUG_LOG'))
			define(DEBUG_LOG,realpath('.').'/debug.log');
		if(!strlen($log))
			$log = DEBUG_LOG;

		// append to log
		$txt = '['.date('n-j-y H:i:s')."] ".str::mixed2str($txt)."\n";
		@file_put_contents($log,$txt,FILE_APPEND);
		}

	// store return value of system commands
	private static $cmdStatus = 0;

	// execute a system command
	static function cmd($cmd,$passthru = false){
		if(ini::get('verbose') >= 1)
			echo $cmd.($passthru ? ' (passthru)' : '')."\n";

		// passthru (raw output gets echoed)
		if($passthru === true or $passthru === 'passthru')
			passthru($cmd,self::$cmdStatus);

		// spawn (launch the process and return immediately)
		elseif($passthru === 'spawn'){
			$ret = proc_open("$cmd &",array(),$foo);
			//proc_close($ret);
			return $ret;
			}

		// exec (store and parse output)
		else{
			exec($cmd,$output,self::$cmdStatus);
			return ra::condense($output,"\n");
			}
		}

	// execute applescript
	static function applescript($script){
		self::cmd("/usr/bin/osascript > /dev/null <<EOT\n$script\nEOT",true);
		}

	// get command status
	static function getStatus(){
		return self::$cmdStatus;
		}

	static function getIPLocation($ip){
		if(!strlen($ip))
			$ip = $_SERVER['REMOTE_ADDR'];
		
		// use hostip.info to lookup physical location of ip
		$lines = file("http://api.hostip.info/get_html.php?ip=$ip&position=true");
		foreach($lines as $line){
			// parse into array
			if(($pos = strpos($line,':')) !== false){
				$key = strtolower(trim(substr($line,0,$pos)));
				$val = trim(substr($line,$pos + 1));
				
				$ret[$key] = $val;
				}
			}

		// breakup country into long and short
		if(isset($ret['country'])
		and ($pos1 = strpos($ret['country'],'(')) !== false
		and ($pos2 = strpos($ret['country'],')')) !== false
		and $pos2 > $pos1){
			$ret['country-code'] = substr($ret['country'],$pos1 + 1,$pos2 - $pos1 - 1);
			$ret['country'] = ucwords(strtolower(trim(substr($ret['country'],0,$pos1))));
			}

		return $ret;
		}

	static function filemtime_remote($uri){
		// open file
		$uri = parse_url($uri);
		$uri['port'] = isset($uri['port']) ? $uri['port'] : 80;
		$handle = @fsockopen($uri['host'], $uri['port']);
		if(!$handle)
			return 0;

		// read line by line
		$ret = 0;
		fputs($handle,"HEAD $uri[path] HTTP/1.1\r\nHost: $uri[host]\r\n\r\n");
		while(!feof($handle)){
			$line = fgets($handle,1024);
			$lines .= $line;
			if(!trim($line))
				break;

			// parse this line
			$col = strpos($line,':');
			if($col !== false){
				$header = trim(substr($line,0,$col));
				$value = trim(substr($line,$col+1));
				
				// found it
				if(strtolower($header) == 'last-modified'){
					$old = date_default_timezone_get();
					date_default_timezone_set('GMT');
					$ret = strtotime($value);
					date_default_timezone_set($old);

					break;
					}
				}
			}
		
		// debug out
		//echo '<!--- '.$lines.' --->';

		fclose($handle);

		return $ret;
		}

	// wait (safely)
	static function wait($start,$max){
		// break if waited to long
		if((time() - $start) > $max)
			return false;

		// let other processes execute
		usleep(rand(1,10000)); // up to a hundredth of a second

		return true;
		}

	}

?>
