<?php

class ra{
	static function condense($ra,$ex=''){
		$started = false;
		foreach($ra as $str){
			$ret .= ($started ? $ex : '').$str;
			$started = true;
			}
		return $ret;
		}

	// re-write this as a regular expression?
	static function strpos($str,$ra,&$found,$endPos = 0,$caseInsensitive = false){
		// case insensitive might be significantly slower
		$func = ($caseInsensitive ? 'stripos' : 'strpos');
	
		$ret = false;
		$found = false;
		if($endPos <= strlen($str))
			foreach($ra as $find){
				// find this
				if(strlen($find))
					$_ret = $func($str,$find,$endPos);

				// found?
				if($_ret !== false
				and ($ret === false
					or $_ret < $ret
					or ($_ret == $ret and strlen($find) > strlen($found))
					)
				){
					$found = $find;
					$ret = $_ret;
					}
				}
	
		return $ret;
		}

	// recursively copy elements of src to dest
	static function copy($src,&$dest){
		if(is_array($src) or is_object($src))
			foreach($src as $k => $i){
				if(is_array($i))
					self::copy($i,$dest[$k]);
				else
					$dest[$k] = $i;
				}
		}

	static function lastKey($ra){
		end($ra);
		$ret = key($ra);
		reset($ra);
	
		return $ret;
		}

	static function toNVP($nvpArray){
		$nvpStr = '';

		foreach($nvpArray as $k => $i)
			$nvpStr .= (strlen($nvpStr) ? '&' : '').
				"$k=".urlencode($i);
		
		return $nvpStr;
		}

	static function fromNVP($str){
		$ret = array();

		// explode nvp string between "&"
		$a = explode("&", $str);
		foreach($a as $k => $i){
			// explode this string between "="
			$aa = explode("=", $i);
			if(sizeof($aa) > 1)
				$ret[urldecode($aa[0])] = urldecode($aa[1]);
			}
		
		return $ret;
		}
	
	static function fromXML($xml){
		return self::fromObject(simplexml_load_string($xml));
		}

	static function fromObject($o){
		$ret = (is_object($o) ? (array)$o : $o);
		if(is_array($ret) and empty($ret))
			$ret = '';
		else{
			foreach($ret as $k => $i)
				$ret[$k] = self::fromObject($i);
			}
		return $ret;
		}

/*

static function raLastVal(&$ra){
	end($ra);
	$ret = current($ra);
	reset($ra);
	
	return $ret;
	}

static function ra2str($ra){
	if(is_array($ra) or is_object($ra))
		return str_replace(array("\n","\t",' '),
			array('','',''),print_r($ra,true));
	return strval($ra);
	}

static function ra2obj($ra){
	if(is_array($ra))
		foreach($ra as $k => $i)
			$ret->$k = $i;
	return $ret;
	}

static function obj2ra(&$obj){
	// loop this object / array
	if(is_object($obj) or is_array($obj)){
		// convert top level to array
		$ret = array();
		foreach((array)$obj as $k => $i){
			$ret[$k] = $i;
			obj2ra($ret[$k]);
			}
		$obj = $ret;
		}
	}

static function moveKey($from,$to,&$obj){
	// will change array order!
	if(is_array($obj))
		raMoveKey($from,$to,$obj);
	else
		objMoveKey($from,$to,$obj);
	}

static function raMoveKey($from,$to,&$ra){
	$tmp = $ra[$from];
	unset($ra[$from]);
	$ra[$to] = $tmp;
	}

static function objMoveKey($from,$to,&$obj){
	$tmp = $obj->$from;
	unset($obj->$from);
	$obj->$to = $tmp;
	}

static function raClosest($ra,$val){
	$closestKey = false;
	
	if(isRa($ra))
		foreach($ra as $k => $i){
			$delta = abs($i - $val);
			if(!isset($closestDelta)
			or $delta < $closestDelta){
				$closestDelta = $delta;
				$closestKey = $k;
				}
			}
	
	return $closestKey;
	}

static function raClosestVal($ra,$val){
	return $ra[raClosest($ra,$val)];
	}

static function raInterlace($arrays){
	$ret = array();
	$keys = array_keys($arrays);
	if(count($keys)){
		foreach($keys as $key)
			$stacks[$key] = $arrays[$key]; // copy

		while(count($stacks)){
			foreach($keys as $key){
				$_k = array_shift($stacks[$key]);
				if(strlen($_k))
					$ret[] = $_k;
				else
					unset($stacks[$key]);
				}
			}
		}
	
	return $ret;
	}

static function raSwappos($pos1,$pos2,$ra){
	$head = array_slice($ra,0,$pos1);
	$b = array_slice($ra,$pos1,1);
	$mid = array_slice($ra,$pos1+1,$pos2-$pos1-1);
	$a = array_slice($ra,$pos2,1);
	$tail = array_slice($ra,$pos2+1);
	return array_merge($head,$a,$mid,$b,$tail);
	}

static function raKeypos($key,$ra){
	$ret = false;
	$pos = 0;
	foreach($ra as $k => $t){
		if($k == $key)
			{$ret = $pos; break;}
		$pos++;
		}
	return $ret;
	}

static function raInsert($pos,$val,$ra){
	$head = array_slice($ra,0,$pos);
	$tail = array_slice($ra,$pos);
	return array_merge($head,$val,$tail);
	}
	
static function raCopy(&$to,$from){
	// copy array
	if(isRa($from) and is_array($to))
		foreach($from as $k => $i)
			$to[$k] = $from[$k];

	// copy object
	elseif(count($from))
		foreach($from as $k => $i)
			$to->$k = $from->$k;
	}

static function raRm(&$obj,$val){
	if(is_array($obj)){
		$pos = array_search($val,$obj);
		if($pos !== false)
			unset($obj[$pos]);
		}
	}

static function raClean(&$obj,$cleanZero=0){
	if(count($obj))
		foreach($obj as $k => $i){
			if(is_object($i))
				continue;
		
			if(empty($i) or ($cleanZero and is_numeric($i) and floatval($i) == 0.0)){
				if(is_array($obj))
					unset($obj[$k]);
				else
					unset($obj->$k);
				}
			}
	}

//
// converts all floats to strings based on precision
// @param array $obj the array or object to convert
//
static function raFloat2str(&$obj,$precision = 2,$skipKeys = array('sku')){
	if(count($obj))
		foreach($obj as $k => $i){
			// skip certain keys
			if(in_array($k,$skipKeys,true))
				continue;
		
			if(is_numeric($i) and strstr($i,'.')){
				if(is_array($obj))
					$ref =& $obj[$k];
				else
					$ref =& $obj->$k;
			
				$ref = strval(round($i,$precision));
				}
			}
	}

static function raSlice(&$ra,$max){
	if(isRa($ra))
		foreach($ra as $k => $i){
			$cnt++;
			if($cnt > $max)
				unset($ra[$k]);
			}
	}

static function uniqueKey($ra,$key,$alt=''){
	// return alternate choice
	if(strlen($alt) and isset($ra[$key]))
		return $alt;

	// return (2), (3)...
	$orig = $key;
	$i = 1;

	while(isset($ra[$key])){
		$i++;
		if($i > 100) {$key = rand(100,9999); break;}
		$key = "$orig ($i)";
		}

	return $key;
	}
*/
}

?>
