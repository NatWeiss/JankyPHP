<?php

class css extends glom{
	private static $includes = array();
	
	static function getHeader($fname = 'c.css'){
		return parent::getHeader(self::$includes,$fname,ini::get('dir-temp').'css.hash');
		}
	
	static function drawLink($script){
		$out = '<link rel="stylesheet" href="'.
			(is_file($script) ? '/' : '').$script.
			'" type="text/css">'."\n";
		return $out;
		}

	static function add($script){
		if(!in_array($script,self::$includes)){
			if(self::isLink($script))
				;//ini::append('replace','head',self::drawLink($script));
			else
				ini::append('replace','css',$script);
			self::$includes[] = $script;
			}
		}
	
	static function isLink($script){
		return (substr($script,0,7) == 'http://'
			or is_file($script));
		}
	}

?>
