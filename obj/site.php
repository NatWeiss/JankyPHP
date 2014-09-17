<?php

class site{
	function think(){
		// clean caches
		self::cleanCaches();

		// rewrite urls
		self::bounce();
		
		// setup replacements
		ini::set('replace','meta',array());
		ini::set('replace','doctype','<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".'<html>');
		
		// set page
		ini::set('page',$_GET['pg']);
		if(!file_exists($this->getPageFname())
		or filesize($this->getPageFname()) <= 0)
			ini::set('page','home');
		}

	function draw($condense = false){
		// get buffer
		$txt = 
			funx::inc(DIR_KIT.'html/header.html').
				ob_get_contents().
			funx::inc(DIR_KIT.'html/footer.html');
		
		// replace buffer contents
		ob_clean();
		$out = self::replace($txt);
		if($condense)
			$out = self::condense($out);
		
		echo $out;
		}

	static function drawPage($fname = '',$echo = true){
		$out = '';
		
		// auto get page
		if(!strlen($fname))
			$fname = self::getPageFname();

		if(is_file($fname)){
			// capture output
			ob_start();
			include($fname);
			$out = ob_get_contents();
			ob_end_clean();
	
			// capture output by calling think, draw
			if(!strlen($out)){
				$className = 'page'.ucfirst(file::name($fname));
				if(class_exists($className,false)){
					ob_start();
					$p = new $className;
					$p->think();
					$out = $p->draw($echo);
					$out .= ob_get_contents();
					ob_end_clean();
					}
				}
			}
		
		return $out;
		}
	
	static function drawVid($urlTube,$width,$wide = true){
		// auto width/height
		$ratio = ($wide ? (380/640) : (344/425));
		$width = intval($width < 100 ? 100 : $width);
		$height = intval($ratio * $width);
		$urlTube .= '&color1=0xb1b1b1&color2=0xcfcfcf&hl=en&feature=player_embedded&fs=1';

		//<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/AMxwGnGsFHk&hl=en&fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/AMxwGnGsFHk&hl=en&fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>

		// draw the vid
		$ret = '<object width="'.$width.'" height="'.$height.'">'.
			'<param name="movie" value="http://www.youtube.com/v/'.$urlTube.'"></param>'.
			'<param name="allowFullScreen" value="true"></param>'.
			'<embed src="http://www.youtube.com/v/'.$urlTube.'" type="application/x-shockwave-flash" allowfullscreen="true" width="'.$width.'" height="'.$height.'"></embed>'.
			'</object>';
		return $ret;
		}
	
	static protected function drawAjax($out){
		ob_end_clean();
		echo 
			(strlen(ini::get('replace','css')) ?
			'<style type="text/css">'.ini::get('replace','css').'</style>' :
			'').
			self::replace($out).
			(strlen(ini::get('replace','onload')) ?
			'<script type="text/javascript">'.ini::get('replace','onload').'</script>' :
			'').
			'';
		exit();
		}

	static protected function getPageFname(){
		return (is_dir('php') ? 'php' : 'text').
			'/'.ini::get('page').".php";
		}
	
	static function setTitle($txt){
		ini::set('replace','title',$txt);
		}

	static function setDescription($txt){
		if(!strlen(ini::get('replace','meta','description')))
			ini::set('replace','meta','description',str::str2metadesc($txt));
		}

	static function setCanonical($url){
		ini::append('replace','head',"<link rel=\"canonical\" href=\"$url\" />");
		}

	static function getMode($modes,$index = 0){
		$mode = $_GET[$index];

		if(is_array($modes)
		and !in_array($mode,$modes))
			$mode = current($modes);
		
		return $mode;
		}
	
	//
	// engine
	//

	static function setRootDir($file){
		define('DIR_ROOT',dirname($file).'/');
		}

	function __construct($indexFname,$showWarnings = true){
		self::setRootDir($indexFname);

		$errFlags = (E_ALL ^ E_NOTICE);
		if(!$showWarnings)
			$errFlags ^= E_WARNING;
		error_reporting($errFlags);
		
		ini_set('register_globals',0);
		ini_set('log_errors',1);
		ini_set('error_log',DIR_ROOT.'/errors');

		ob_start();
		}
	
	function __destruct(){
		ob_end_flush();
		}

	private static function replace($buffer){
		// replace the "{!X_something}" with ini::get('replace','something')
		foreach(ini::get('replace') as $key => $replacement)
			$buffer = str_replace("{!X_$key}",$replacement,$buffer);

		return $buffer;
		}
	
	private static function condense($buffer){
		$buffer = str::trimLines($buffer);
		return $buffer;
		}

//
// function bounce
//
// transparently redirects url "mysite.com/something/else"
// to "mysite.com/?0=something&1=else"
//
// to do:
// asking for /bla/x/32897e.jpg returns it with mime type
//
	static function bounce(){
		// truncate everything before "#" (for AJAX)
		$uri = $_SERVER['REQUEST_URI'];
		if(($pos = strpos($uri,0,'#')) !== false)
			$uri = substr($uri,$pos);

		// treat everything before the "?" as URI
		if(($pos = strpos($uri,'?')) !== false){
			$get_vars = substr($uri,$pos + 1);
			$uri = substr($uri,0,$pos);
			}

		// explode URI by "/"
		if($uri[0] == '/')
			$uri = substr($uri,1);
		if($uri[strlen($uri) - 1] == '/')
			$uri = substr($uri,0,-1);
		$uri = explode('/',$uri);

		// parse into _GET[pg], _GET[0], _GET[1]...
		$cnt = 0;
		$_GET['pg'] = array_shift($uri);
		foreach($uri as $item)
			$_GET[$cnt++] = $item;

		// parse original get vars
		if(strlen($get_vars)){
			foreach(explode('&',$get_vars) as $txt){
				list($key,$val) = explode('=',$txt);
				$_GET[$key] = $val;
				}
			}
		}
	
	static function drawFile($fname){
		// settings
		ini_set('zlib.output_compression','Off');
		ini_set('output_buffering','Off');

		// clean output buffers
		for($i = 0; $i < ob_get_level(); $i++){
			ob_end_clean();
			if($i > 10)
				break;
			}

		// content-type based on extension
		$types = array(
			'default' => array('type'=>'application/octet-stream','attach'=>true),
			'mp3' => 'audio/mpeg',
			'mov' => 'video/quicktime',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'gif' => 'image/gif',
			'pdf' => 'application/pdf',
			'zip' => array('type'=>'application/zip','attach'=>true),
			'doc' => array('type'=>'application/msword','attach'=>true),
			);

		// get type
		$ext = file::ext($fname);
		$t = (isset($types[$ext]) ? $types[$ext] : current($types));
		if(!is_array($t))
			$t = array('type' => $t);

		// headers
		$id = str::bc36(time());
		$chunk = 1024*1024;
		$time_limit = (int)($chunk / 100);
		$filesize = filesize($fname);
		header("Cache-Control:");
		header("Cache-Control: public");
		header("Pragma: ");
		header('Content-Type: '.$t['type']);
		header('Content-Disposition: '.
			($t['attach'] ? 'attachment' : 'inline').';'.
			' filename="'.basename($fname).'"');
			//' filename="'.file::name($fname).'-'.$id.'.'.file::ext($fname).'"');
		header("Content-Transfer-Encoding: binary\n");

		// check if http_range is sent by browser (or download manager)
		header("Accept-Ranges: bytes");
		if(isset($_SERVER['HTTP_RANGE'])){
			list($a,$range) = explode("=",$_SERVER['HTTP_RANGE']);
			str_replace($range,"-",$range);
			$filesize2 = $filesize - 1;
			$new_length = $filesize2 - $range;
			header("HTTP/1.1 206 Partial Content");
			header("Content-Length: $new_length");
			header("Content-Range: bytes $range$filesize2/$filesize");
			$filesize = $new_length;
			}
		else{
			$filesize2 = $filesize-1;
			header("Content-Range: bytes 0-$filesize2/$filesize");
			}
		header("Content-Length: ".$filesize);

		// draw
		$fp = fopen($fname,'rb');
		if($fp){
			// resume
			if($range > 0)
				fseek($fp,$range);

			// send
			while(!feof($fp) and connection_status()==0){
				set_time_limit($time_limit);
				$content = fread($fp,$chunk);
				echo $content;
				ob_flush();
				flush();
				
				$sent += strlen($content);
				unset($content);
				//funx::debug("$id sent ".(int)($sent/1024)."k".
				//	", memory used ".(int)(memory_get_usage(true)/1024)."k".
				//	", time limit ".(int)($time_limit/60)."m");
				}

			fclose($fp);
			}
		
		funx::debug("$id done ($sent of $filesize) (connection_status==".(int)connection_status().")");
		
		return (!connection_aborted()
			and connection_status()==0
			and $sent >= $filesize);
		}

	static function cleanCaches(){
		if($_GET['cache'] == 'clear'){
			$files = array(
				ini::get('blog-cache-fname'),
				ini::get('twitter-cache-fname'),
				);
			foreach($files as $fname){
				if(is_file($fname))
					unlink($fname);
				}
			}
		}
	}

?>
