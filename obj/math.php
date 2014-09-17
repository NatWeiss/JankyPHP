<?php

class math{
	static function clamp($val,$min,$max){
		if($val >= $max)
			$val = $max;
		elseif($val <= $min)
			$val = $min;
		return $val;
		}
	}

?>
