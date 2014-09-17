<?php

class glom{
	const DEBUG = false;

	static protected function getHash($includes){
		$ret = '';
		if(is_dir('js'))
			$ret .= "js\n".filectime('js')."\n";
		if(is_dir('css'))
			$ret .= "css\n".filectime('css')."\n";
		
		foreach($includes as $i)
			$ret .= $i."\n".(is_file($i) ? filectime($i)."\n" : '');
		
		//if(self::DEBUG)
		//	funx::debug(__METHOD__." Hash=".crc32($ret)."\n$ret");
		return crc32($ret);
		}
	
	// return a list of files we have included
	static protected function getFnames($includes){
		$ret = array();
		
		foreach($includes as $i)
			if(is_file($i))
				$ret[] = $i;
		
		return $ret;
		}

	static function getHeader($includes,$masterFname,$hashFname){
		// check hash
		$hash = self::getHash($includes);
		$previousHash = trim(file_get_contents(realpath($hashFname)));
		
		if(self::DEBUG)
			funx::debug("Exists $masterFname? ".intval(file_exists($masterFname)).
				" New Hash: $hash, Old Hash: $previousHash");
		if(!file_exists($masterFname)
		or $hash != $previousHash)
			self::saveHeader($hash,$includes,$masterFname,$hashFname);
		
		// return header information
		if(strstr($masterFname,'js'))
			ini::append('replace','head',
				'<script type="text/javascript" src="/'.$masterFname.'"></script>'."\n");
		elseif(strstr($masterFname,'css'))
			ini::append('replace','head',
				'<link rel="stylesheet" href="/'.$masterFname.'" type="text/css">'."\n");
				//'<link rel="stylesheet" href="/'.$masterFname.'" type="text/css" media="Screen">'."\n");
		
		return $ret;
		}

	private static function saveHeader($hash,$includes,$masterFname,$hashFname){
		// list files
/*		$files = self::getFnames($includes);
		$txt = '/'.'* '.
			$hash."\n".
			date('n-j-y h:i')."\n".
			var_export($files,true).' *'.'/'."\n";
*/
		// loop added js
		foreach($includes as $i){
			if(is_file($i))
				$txt .= self::getScript($i);
			else
				$txt .= "\n$i\n\n";
			}

		file_put_contents($masterFname,$txt);
		file_put_contents($hashFname,$hash);
		chmod($masterFname,0605);
		chmod($hashFname,0600);
		if(self::DEBUG)
			funx::debug(__METHOD__." Wrote a new '$masterFname' and '$hashFname' (".
				count($includes)." includes)");
		}

	// draw a file
	protected static function getScript($name){
		$ret = file_get_contents($name);
		return $ret."\n";
		}
	
	protected static function strip($ret){
		// erase tabs
		$ret = str_replace(array("\t"),array( '' ),$ret);

		// strip c++ style comments
		$lines = explode("\n",$ret);
		foreach($lines as $i => $line)
			if(($pos = strpos($line,'/'.'/')) !== false)
				$lines[$i] = substr($line,0,$pos);
		
		$ret = ra::condense($lines,"")."\n";
		
		return $ret;
		}
	}

?>
