<?php

class str{
	static function substr_words($str,$num_words){
		return implode(' ',array_slice(explode(' ',$str),0,$num_words));
		}

	static function startsWith($str,$start)
		{return (substr($str,0,strlen($start)) == $start);}

	static function isOnlyUppercase($str)
		{return (preg_match('{^[A-Z]+$}',$str));}
	
	static function isOnlyLowercase($str)
		{return (preg_match('{^[a-z]+$}',$str));}
	
	static function isOnlyNumbers($str)
		{return (preg_match('{^[0-9]+$}',$str));}
	
	static function isDate($date)
		{return (eregi('^([0-9]{1,2})([/]+)([0-9]{1,2})([/]+)([0-9]{4,4})$',$date));}
	
	static function isEmail($email)
		{return (eregi('^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$',$email));}

	static function escapeQuote($str)
		{return str_replace("'","\'",$str);}

	static function br2nl($str)
		{return preg_replace('{\<br[^\<\>]*\>}i',"\n",$str);}

	static function condenseWhitespace($str,$rpl = ' ')
		{return preg_replace('{[ \t\r\n]+}',$rpl,$str);}

	static function condenseNonLetters($str,$rpl = ' ')
		{return preg_replace('{[^a-zA-Z0-9]+}',$rpl,$str);}

	static function condenseTags($str,$rpl = ' ')
		{return preg_replace('{\<[^\<\>]*\>}',$rpl,$str);}
	
	static function condenseHtmlChars($str,$rpl = ' ')
		{return preg_replace('{\&.{1,7}\;}',$rpl,$str);}
	
	static function condenseNonXML($str,$rpl = '')
		{return preg_replace('{[\&\'\"\<\>]}',$rpl,$str);}
	
	static function condenseLinks($str,$rpl = '')
		{return preg_replace('{(\<a href=[^\>]+\>)|(</a>)}',$rpl,$str);}
	
	static function justWords($str){
		return trim(str::condenseWhitespace(str::condenseNonLetters(str::condenseTags($str))));
		}

	static function bc36($n)
		{return base_convert($n,10,36);}
	
	static function bc10($n)
		{return base_convert($n,36,10);}
	
	static function trimLines($str){
		$out = '';
		foreach(explode("\n",$str) as $line)
			$out .= trim($line)."\n";
		return $out;
		}

	static function between($str,$start,$end,&$endPos,$caseInsensitive = false){
		if(!strlen($str) or !strlen($start) or !strlen($end))
			return false;
	
		if(!is_array($start))
			$start = array($start);
		if(!is_array($end))
			$end = array($end);
	
		$pos1 = ra::strpos($str,$start,$found,$endPos,$caseInsensitive);
		if($pos1 === false)
			return false;
		$pos1 += strlen($found);

		$pos2 = ra::strpos($str,$end,$found,$pos1,$caseInsensitive);
		if($pos2 === false)
			return false;
		$endPos = $pos2;
	
		return substr($str,$pos1,$pos2 - $pos1);
		}

	static function mixed2str($var){
		return (is_string($var) ? $var : encoder::encodeSimple($var));
		}

	static function text2html($txt){
		return '<pre>'.htmlentities($txt).'</pre>';
		}

	static function date2ago($date){
		$delta = (time() - $date);
		if($delta < 60)
			$ret = 'a minute ago';
		else if($delta < 120)
			$ret = 'couple of minutes ago';
		else if($delta < (45*60))
			$ret = intval($delta / 60).' minutes ago';
		else if($delta < (90*60))
			$ret = 'an hour ago';
		else if($delta < (24*60*60))
			$ret = intval($delta / 3600).' hours ago';
		else if($delta < (48*60*60))
			$ret = '1 day ago';
		else
			$ret = intval($delta / 86400).' days ago';

		return $ret;
		}
	
	static function str2metadesc($txt){
		$txt = str::condenseHtmlChars($txt,' ');
		$txt = str::condenseTags($txt,' ');
		$txt = str_replace(array('"',' .'),array("'",'.'),$txt);
		$txt = trim(str::condenseWhitespace($txt));
		if(strlen($txt) > 320)
			$txt = substr($txt,0,317).'...';
		return $txt;
		}
	}

?>
