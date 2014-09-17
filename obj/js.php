<?php

class js extends glom{
	private static $toGlom = array();
	private static $isAdded = array();
	private static $fname = 'j.js';
	
	static function setFname($fname){
		self::$fname = $fname;
		}
	
	static function getHeader(){
		return parent::getHeader(
			self::$toGlom,
			self::$fname,
			ini::get('dir-temp').self::$fname.'.hash');
		}

	static function draw($script){
		if(substr($script,0,7) == 'http://')
			$i = ' src="'.$script.'">';
		elseif(is_file($script))
			$i = ' src="/'.$script.'">';
		else
			$i = ">\n$script\n";
		return '<script type="text/javascript"'.$i."</script>\n";
		}

	static function add($script,$where = 'head'){
		// if not already added..
		if(!in_array($script,self::$isAdded)){
			// force adding it to the html head
			if($where != 'head')
				ini::append('replace',$where,self::draw($script));
			else
				self::$toGlom[] = $script;
			
			// add it to "already added" list
			self::$isAdded[] = $script;
			}
		}
	
	static function add_slimbox(){
		self::add_mootools();
		self::add('js/ext/slimbox/js/slimbox.js');
		css::add('css/slimbox.css');
		}
	
	static function add_soundmanager($where = 'head',$debug = false){
		css::add('#soundmanager-debug {position:absolute;right:0px;bottom:0px;width:50em;height:18em;overflow:auto;background:#fff;color:#000;margin:1em;padding:1em;border:1px solid #999;font-family:"lucida console",verdana,tahoma,"sans serif";font-size:x-small;line-height:1.5em;}');
		if($debug){
			self::add('js/soundmanager2.js',$where);
			self::add("soundManager.url = '/pics/soundmanager2.swf';",$where);
			}
		else{
			self::add('js/sm2.js',$where);
			self::add("soundManager.url = '/pics/soundmanager2.swf'; soundManager.debugMode = false;",$where);
			}
		}

	static function add_mootools(){
		self::add('js/ext/mootools.js');
		}
	
	static function add_plaxo(){
		self::add('http://www.plaxo.com/css/m/js/util.js','js');
		self::add('http://www.plaxo.com/css/m/js/basic.js','js');
		self::add('http://www.plaxo.com/css/m/js/abc_launcher.js','js');
//		self::add('function onABCommComplete() {}');
 		}

	static function openWindow($loc,$width,$height,$ex){
		$ret = self::getScript('js/templates/open-window.js');
		$ret = self::strip(sprintf($ret,$loc,$width,$height,$ex));
		
		return $ret;
		}

	static function trackScript($url){
		$rex = file::getRex();
		$trackId = $url;
		return "autoclick('$rex','','$trackId');";
		}
	}

?>
