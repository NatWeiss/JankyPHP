<?php

/*
2do:
turn all '< form' into '<form' and 'name =' into 'name=' when parsing
*/

//
// read an external website, remember cookies, parse forms, etc...
//
class robot{
	const SAVE_PAGES = true;
	
	static private $curlCmd = '';
	private $lastLink = '';
	private $cookiejarFname = '';

	protected function __construct($name,$dir,$dirTmp){
		set_time_limit(0);

		if(!is_dir($dir))
			trigger_error(__METHOD__." invalid directory '$dir'",E_USER_ERROR);
		if(substr($dirTmp,-1) != '/')
			$dirTmp .= '/';
		if(!is_dir($dirTmp))
			mkdir($dirTmp);

		$this->count = 0;
		$this->name = $name;
		$this->logFname = $dir.$name.'.log';
		$this->dirTmp = $dirTmp;
		$this->cookiejarFname = $this->dirTmp."$name.cookiejar";
		self::$curlCmd = trim(`which curl`);
		
		// delete stashed pages
		if(self::SAVE_PAGES){
			$files = glob($this->dirTmp."$this->name-*");
			foreach($files as $file){
				@unlink($file);
				funx::debug("Removed: '$file'");
				}
			}
		}
	
	// get contents of url and return
	function get($link,$data = array(),$dataMethod = 'get',$curlParams2 = ''){
		// remember last page as referer
		if(!strlen($this->getLastLink()))
			$this->setLastLink($link);
		
		// parse dataMethod
		switch($dataMethod){
			case 'get': $dataMethod = '-G -d'; break;
			
			default:
			case 'post': $dataMethod = '-d'; break;
			}

		// draw data string
		if(is_array($data))
			$curlParams = $dataMethod.' "'.self::ra2url($data).'"';

		$link = str_replace('&amp;','&',$link);

		// fix relative URLs
		if(!stristr($link,'://')){
			$ra = parse_url($this->getLastLink());
			if($link[0] != '/')
				$link = '/'.$link;
			$ra['path'] = dirname($ra['path']);
			if(substr($ra['path'],-1) == '/')
				$ra['path'] = substr($ra['path'],0,-1);
			
			$newLink = $ra['scheme'].'://'.$ra['host'].$ra['path'].$link;
			funx::debug(__METHOD__."($this->count): fixing relative URL\n\t$link =>\n\t$newLink");
			$link = $newLink;
			}

		// build curl command
		$ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1) Gecko/20061010 Firefox/2.0';
		$fnameTrace = $this->dirTmp.'robot-trace';
		$cmd = self::$curlCmd.
			" -s -L  -A \"$ua\" --trace-ascii $fnameTrace".
			" -b $this->cookiejarFname -c $this->cookiejarFname $curlParams".
			" -e \"".$this->getLastLink()."\" $curlParams2 \"$link\"";
		$this->setLastLink($link);

		// execute
		ob_start();
		passthru($cmd);
		$res = ob_get_contents();
		ob_end_clean();

		// stash pages
		if(self::SAVE_PAGES){
			$trace = "Executing:\n".
				str_replace(' -',"\n\t -",$cmd)."\n\n".
				@file_get_contents($fnameTrace);
			file_put_contents($this->dirTmp."$this->name-$this->count.trace",$trace);
			file_put_contents($this->dirTmp."$this->name-$this->count.html",$res);
			funx::debug(__METHOD__."($this->count): $link");
			}
		$this->count++;
		@unlink($fnameTrace);

		return $res;
		}

	function parse($url,$data = array(),$dataMethod = 'get'){
		// get raw page
		if(stristr($url,'<html'))
			$res = $url;
		else
			$res = $this->get($url,$data,$dataMethod);

		// parse forms
		$ret = array();
		$i = 0;
		$forms = self::getAllTags($res,'form',array('name','method','action','id'));
		foreach($forms as $form){
			// form properties
			foreach($form as $key => $val)
				if($key != 'text')
					$ret[$i][$key] = $val;
			
			// parse inputs
			$endPos = 0;
			do{
				$item = str::between($form['text'],'<input','>',$endPos,true);

				if($item !== false
				and ($name = self::parseProperty($item,'name')) !== false)
					$ret[$i]['input'][$name] = self::parseProperty($item,'value');

				}while($item !== false);
			
			// parse select
			$selects = self::getAllTags($form['text'],'select',array('name'));
			foreach($selects as $select){
				// add the select item
				if(strlen($select['name']))
					$ret[$i]['input'][$select['name']] = '';
				
				// see if we can find which option is selected
				$end = (stristr($select['text'],'</option>') ? '</option>' : '<');
				unset($selected);
				$endPos = 0;
				do{
					$option = str::between($select['text'],'option',$end,$endPos,true);
					if($option !== false
					and ($optionVal = self::parseProperty($option,'value')) !== false){
						// set value initially, then only if selected
						if(!isset($selected)
						or stristr($option,'selected'))
							$ret[$i]['input'][$select['name']] = $selected = $optionVal;
						}
					}while($option !== false);
				}
			
			// parse textarea
			$textareas = self::getAllTags($form['text'],'textarea',array('name'));
			foreach($textareas as $textarea)
				$ret[$i]['input'][$textarea['name']] = $textarea['text'];

			$i++;
			}
		
		//funx::debug("Parsed forms:"); funx::debug($ret);	
		return $ret;
		}

	function submit($form){
		return $this->get($form['action'],$form['input'],$form['method']);
		}

	function findForm($forms,$search){
		if(!is_array($search)){
			funx::debug(__METHOD__." Requires search to be an array");
			return false;
			}
		
		// automatically (get html and) convert to forms
		if(is_string($forms))
			$forms = $this->parse($forms);
		
		foreach($forms as $form){
			$invalid = false;
			foreach($search as $k => $i){
				// must have form['something'] = "...someother..."
				if(is_string($i) and !stristr($form[$k],$i))
					$invalid = true;
				
				// must have form['something']['somother']
				elseif(is_array($i)){
					foreach($i as $_k => $_i)
						if(!isset($form[$k][$_i])){
							$invalid = true;
							break;
							}
					}
				
				if($invalid)
					break;
				}
			
			if($invalid)
				$form = false;
			else
				break;
			}
		
		return $form;
		}

	// convert "< a" to "<a" and "/ form >" to "/form>"
	static function preparseHTML($html){
		// doesnt work as expected yet...
		// (it currently converts "< a" to "a"
		return preg_replace('{(\<[ \t\r\n]+)|(\/[ \t\r\n]+[\s]+\>)}','',$html);
		}

	static function parseProperty($txt,$name){
		$ret = false;
		
		$ret = str::between($txt,"$name=\"",'"',$end = 0,true);
		if($ret === false)
			$ret = str::between($txt,"$name='","'",$end = 0,true);
		if($ret === false)
			$ret = str::between($txt.'>',"$name=",array(' ','>'),$end = 0,true);

		return $ret;
		}
	
	static function getAllTags($txt,$type,$props = array()){
		if(strlen($type) > strlen($txt)){
			funx::debug(__METHOD__.' tag type is longer than txt');
			return false;
			}
		if(!is_array($props))
			$props = array();
		
		$ret = array();
		do{
			$tag = self::extractTag($txt,$type);
			if($tag !== false
			and ($pos1 = strpos($tag,'>')) !== false
			and ($pos2 = strrpos($tag,'<')) !== false){
				// get text
				$r['text'] = substr($tag,$pos1 + 1,$pos2 - $pos1 - 1);
				
				// parse properties
				$tag = substr($tag,0,$pos1);
				foreach($props as $prop){
					$val = self::parseProperty($tag,$prop);
					if(strlen($val))
						$r[$prop] = $val;
					}
				
				$ret[] = $r;
				}
			}while($tag !== false);
		
		return $ret;
		}
	
	static function extractTag(&$txt,$type){
		// find this tag
		$endPos = 0;
		$start = "<$type";
		$end = "/$type>";
		$ret = str::between($txt,$start,$end,$endPos,true);
		if($ret !== false){
			// has depth?
		 	while(($pos = stripos($ret,$start)) !== false)
		 		$ret = substr($ret,$pos + strlen($start));
		 	
		 	// re-form
		 	$ret = $start.$ret.$end;
		 	$endPos += strlen($end);

			// "cut" the tag
			$a = substr($txt,0,$endPos - strlen($ret));
			$b = substr($txt,$endPos);
			$txt = $a.$b;
			}

		return $ret;
		}

	function parseAllBetween($res,$start,$end){
		$ret = array();

		$endPos = 0;
		do{
			$str = str::between($res,$start,$end,$endPos,true);
			if(strlen($str))
				$ret[] = $str;
			} while($str !== false);

		return $ret;
		}

	static function ra2url($ra){
		$ret = '';
		
		foreach($ra as $key => $val){
			if(is_array($val) and count($val) >= 1)
				foreach($val as $k => $i)
					$ret .= self::urlArg($k,$i,$pre);
			else
				$ret .= self::urlArg($key,$val,$pre);
			}
		
		return $ret;
		}
	
	private function urlArg($key,$val,&$pre){
		// build this arg
		if($val[0] == '@')
			$ret = $pre.$key.'='.$val;
		else
			$ret = $pre.urlencode($key).'='.urlencode($val);
		
		// next time add '&'
		$pre = '&';

		return $ret;
		}

	static function link2input(&$link){
		// parse link
		$url = parse_url($link);
		$pairs = explode('&',$url['query']);
		foreach($pairs as $pair){
			$out = explode('=',$pair);
			$input[$out[0]] = $out[1];
			}
		$link = $url['scheme'].'://'.$url['host'].$url['path'];

		return $input;
		}
	
	function hasCookie(){
		clearstatcache();
		return file_exists($this->cookiejarFname);
		}
	
	function isCookieStale($maxMinutes = 30){
		$life = (file::life($this->cookiejarFname) / 60);
		return ($life >= $maxMinutes);
		}
	
	function deleteCookie(){
		@unlink($this->cookiejarFname);
		}

	static function copyInput($form,$keys){
		$ret = array();

		foreach($keys as $key)
			$ret[$key] = $form['input'][$key];
		
		return $ret;
		}

	function getLastLink(){
		return $this->lastLink;
		}

	function setLastLink($link){
		$this->lastLink = $link;
		}
	
	function decho($out,$append = "\n"){
		$this->log($out,$append);
		if(!ini::get('silent'))
			echo $out.$append;
		}

	function log($out,$append = "\n"){
		file_put_contents($this->logFname,$out.$append,FILE_APPEND);
		}

	protected function login(){
		}
	
	protected function logout(){
		}
	}

?>
