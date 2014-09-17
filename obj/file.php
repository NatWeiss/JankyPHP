<?php

class file{
 	// return seconds since file modified
	static function life($fname){
		return (time() - filectime($fname));
		}

	// returns filename with or without extension
	static function name($fname,$extension = false){
		$fname = basename($fname);
		if(!$extension
		and ($pos = strrpos($fname,'.')) !== false)
			$fname = substr($fname,0,$pos);

		return $fname;
		}

	// get extension
	static function ext($fname){
		$pos = strrpos($fname,'.');
		return ($pos === false ? '' : substr($fname,$pos + 1));
		}

	// return filename appropriate for shell command
	static function shellify($fname){
		return escapeshellarg($fname);
		//if(!strstr($fname,'\\'))
		//	$fname = str_replace(array(' ',':',"'",'&'),array('\ ','\\:',"\'",'\&'),$fname);
		//return $fname;
		}

	// returns true if $fname is an image
	static function isImage($fname){
		$extensions = array('gif','jpg','jpeg','png');
		$info = pathinfo($fname);

		return (in_array(strtolower($info['extension']),$extensions));
		}

	// decode a string into an object
	static function decode($var){
		return encoder::decode($var);
		}

	// encode an object as a string for saving
	static function encode($var){
		return encoder::encode($var);
		}

	// get current action id
	static function getRex($rex = ''){
		if(!strlen($rex))
			$rex = (strlen($_POST['rex']) ? $_POST['rex'] : $_GET['rex']);
		if(!strlen($rex))
			$rex = self::makeRex();
		return $rex;
		}
	
	// load all already done actions
	private static function loadRex($fname,&$rex){
		$rex = self::getRex($rex);
		return self::decode(@file_get_contents($fname));
		}

	// has the user already done this?
	static function hasRex($fname = 'sessions/submit.rex',$rex = ''){
		$ra = self::loadRex($fname,$rex);
		$ret = (is_array($ra) and strlen($rex) and is_numeric($ra[$rex]) and $ra[$rex] >= 1);
		//if(!$ret) funx::debug("rex does not have '$rex'");
		return $ret;
		}

	// remember that the user has already done this
	static function setRex($fname = 'sessions/submit.rex',$rex = ''){
		$ra = self::loadRex($fname,$rex);
		$ra[$rex]++;
		file_put_contents($fname,self::encode($ra));
		}

	// make a unique user has done this id
	static function makeRex(){
		return str::bc36(strval(time()).str_replace('.','',$_SERVER['REMOTE_ADDR']));
		}

	//
	// find files
	//   * 1st arg can be an array of files and or dirs or just a file
	//
	static function find($filesOrDirs,$pattern){
		if(!is_array($filesOrDirs))
			$filesOrDirs = array($filesOrDirs);

		$ret = array();
		foreach($filesOrDirs as $i){
			// add single file
			if(is_file($i) and strstr($i,str_replace('*','',$pattern)))
				$ret[] = $i;

			// search dir
			elseif(is_dir($i)){
				// trim dir
				if(substr($i,-1) == '/')
					$i = substr($i,0,-1);

				// find all files
				$newFiles = explode("\n",trim(funx::cmd("find '$i' -name '$pattern' -print")));
				if(is_array($newFiles))
					$ret = array_merge($ret,$newFiles);
				}
			}

		// prune symlinks and blanks
		$ret = array_unique($ret);
		foreach($ret as $k => $file)
			if(is_link($file) or !strlen($file))
				unset($ret[$k]);

		return (is_array($ret) ? $ret : array());
		}

	//
	// find a single file in directories
	//
	static function findSingle($file,$dirs = ''){
		if(!is_file($file) and strlen($file)){
			// args to array
			if(!is_array($dirs))
				$dirs = array($dirs);

			// search dirs
			foreach($dirs as $dir){
				if(ini::get('verbose'))
					echo "Searching for '$file'...\n";
				$files = file::ls("$dir/$file*");
				if(count($files)){
					$file = current($files);
					break;
					}
				}

			// warning
			if(!is_file($file) and ini::get('verbose')){
				echo "Cannot find '$file'";
				foreach($dirs as $dir)
					echo " or '$dir/$file*'";
				echo "\n";
				}
			}

		return $file;
		}

	//
	// open file
	// optionally uses specified application
	//
	static function open($file,$app = ''){
		// die
		if(!file_exists($file))
			die("Cannot open '$file'\n");

		// run
		$file = file::shellify($file);
		if(strlen($app))
			$app = "-a $app";
		funx::cmd("open $app $file",true);
		}

	//
	// list files
	//
	static function ls($pattern,$args = '-t'){
		// list
		$ret = explode("\n",trim(funx::cmd("ls $args $pattern 2> /dev/null")));

		// prune
		foreach($ret as $k => $i)
			if(!strlen($i))
				unset($ret[$k]);

		return (is_array($ret) ? $ret : array());
		}

	//
	// lock
	//
	static function lock($path){
		$lockPath = $path.'.lock';
		//funx::debug(__FUNCTION__." $lockPath");

		// get the lock
		$start = time();
		while(@mkdir($lockPath) === false){
			// delete stale lock
			clearstatcache();
			if(file_exists($lockPath)){
				$life = self::life($lockPath);
				if($life > 5){
					funx::debug(__FUNCTION__." $lockPath: unlocked ($life seconds stale)");
					self::unlock($path);
					}
				}
			else{
				funx::debug(__FUNCTION__." $lockPath: cannot mkdir and path does not exist");
				break;
				}

			//funx::debug(__FUNCTION__." $lockPath: waiting...");
			usleep(100000); // a tenth of one second
			if(funx::wait($start,15) === false){
				funx::debug(__FUNCTION__." $lockPath: waited to long and didnt unlock (lock life=$life)");
				break;
				}
			}

		return true;
		}

	//
	// unlock
	//
	static function unlock($path){
		$lockPath = $path.'.lock';
		//funx::debug(__FUNCTION__." $lockPath");
		@rmdir($lockPath);
		}
	}

?>
