<?php

class encoder{
	//
	// public
	//
	
	// encode
	static function encode($o){
		switch(ini::get('encoder')){
			case 'simple':
				return self::encodeSimple($o);
			
			default:
			case 'serialize':
				return addcslashes(serialize($o),"\0..\37!@\@\177..\377")."\n";
			}
		}
	
	// encode as readable text
	static function encodeSimple($o){
		return '}{'.(self::isObject($o) ? get_class($o) : '')."\n".
			self::simplify($o);
		}

	// decode
	static function decode($o){
		$start = substr($o,0,2);
		if($start == '}{')
			$ret = self::decodeSimple($o);
		else
			$ret = unserialize(stripcslashes($o));
		
		return $ret;
		}
	
	// decode simple text into an object
	static function decodeSimple($o){
		// get type
		$pos = strpos($o,"\n");
		if($pos !== false){
			$type = substr($o,2,$pos - 2);
			$endPos = $pos + strlen("\n");
			}
	
		// create object or array
		if(strlen($type) and $type != 'array' and class_exists($type))
			$ret = new $type;
		else
			$ret = array();
	
		self::complexify($ret,$o,$endPos);
	
		//funx::debug(var_export($ret,true),'object decoded');
		return $ret;
		}

	// decode session data
	static function decodeSession($data){
		while(true){
			$pos = strpos($data,'|');
			if($pos === false) break;
			
			$k = substr($data,0,$pos);
			$data = substr($data,$pos+1);
			$out[$k] = '';
			
			$pos = strpos($data,'|');
			$tmp = ($pos === false ? $data : substr($data,0,$pos));
			$pos2 = strrpos($tmp,';');
			$pos3 = strrpos($tmp,'}');
			if($pos2 === false and $pos3 === false) break;
			if($pos3 > $pos2) $pos2 = $pos3;
			
			$i = substr($data,0,$pos2+1);
			$out[$k] = unserialize($i);
			$data = substr($data,$pos2+1);
			}
		
		return $out;
		}

	//
	// private
	//
	private static function isObject($o){
		return (is_object($o) or gettype($o) == 'object');
		}
	
	// draw readable text
	private static function simplify($o,$indent = ''){
		$out = '';
		
		if(is_array($o) or self::isObject($o))
			foreach($o as $k => $i){
				$out .= $indent;
				if(is_array($i))
					$out .= $k."\n".
						self::simplify($i,$indent."\t");
				elseif(self::isObject($i))
					$out .= '}{'.get_class($i)."\t".
						$k."\n".
						self::simplify($i,$indent."\t");
				else
					$out .= $k."\t".
						str_replace(array("\t","\n","\r"),array('\t','\n',''),strval($i)).
						"\n";
				}
		
		return $out;
		}
	
	// take simple text and turn it into an object
	private static function complexify(&$ret,&$data,&$endPos,$depth = 0){
		$isObject = is_object($ret);
	
		while($endPos < strlen($data)){
			$pos = strpos($data,"\n",$endPos);
			if($pos === false)
				break;
			else{
				// get line
				$line = substr($data,$endPos,$pos - $endPos);
	
				// depth
				$out = explode("\t",$line);
				$max = count($out);
				for($thisDepth = 0; $thisDepth < $max; $thisDepth++){
					if(strlen($out[$thisDepth]))
						break;
					else
						unset($out[$thisDepth]);
					}
	
				// climb out of depth
				if($thisDepth != $depth)
					return;
	
				// shorten data
				$endPos = $pos + strlen("\n");
				
				// new array (go in depth)
				$name = current($out);
				if(count($out) == 1){
					if($isObject){
						$ret->$name = array();
						self::complexify($ret->$name,$data,$endPos,$depth + 1);
						}
					else{
						$ret[$name] = array();
						self::complexify($ret[$name],$data,$endPos,$depth + 1);
						}
					}
				
				// new class (go in depth)
				elseif(substr($name,0,2) == '}{'){
					$type = substr($name,2);
					$name = next($out);
	
					if($isObject){
						$ret->$name = new $type;
						self::complexify($ret->$name,$data,$endPos,$depth + 1);
						}
					else{
						$ret[$name] = new $type;
						self::complexify($ret[$name],$data,$endPos,$depth + 1);
						}
					}
				
				// new member
				else{
					$val = next($out);
					if(is_string($val))
						$val = str_replace(array('\t','\n'),array("\t","\n"),$val);
	
					if($isObject)
						$ret->$name = $val;
					else
						$ret[$name] = $val;
					}
				}
			}
		}
	}	
	

?>
