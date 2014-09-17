<?php

class browser{
	const THUMB_W = 100;

	function __construct(){
		set_time_limit(0);
		ini_set('memory_limit','128M');
		}
	
	function addAll($search){
		foreach(glob($search) as $root)
			$this->addDirs($root);
		}

	function addDirs($root){
		// find directories with images
		foreach(glob("$root/*",GLOB_NOSORT) as $dir){
			if(is_dir($dir)){
				$images = $this->getImages($dir);
				if(is_array($images)
				and count($images) >= 1)
					$this->dirs[] = $dir;
				else
					$this->addDirs($dir);
				}
			}
		}
	
	function draw($echo = true){
		if(in_array($_GET['dir'],$this->dirs))
			$out .= $this->drawImages($_GET['dir']);
		else
			$out .= $this->drawDirs();
		
		$out = $this->drawHTML($out);
		
		if($echo)
			echo $out;
		else
			return $out;
		}
	
	private function getImages($dir){
		return glob("$dir/{*.jpg,*.JPG}",GLOB_BRACE);
		}

	private function drawDirs(){
		$last = '';
		foreach($this->dirs as $dir){
			$d = dirname($dir);
			if($d != $last){
				$out .= "<h1>$d</h1>";
				$last = $d;
				}
			
			$out .= 
				'<a href="?dir='.$dir.'">'.
				basename($dir).
				'</a> : ';
			}
		
		return $out;
		}
	
	private function drawImages($dir){
		$files = $this->getImages($dir);
		foreach($files as $fname){
			$out .= '<a href="'.$fname.'">'.
				'<img width="'.self::THUMB_W.'" src="'.
					img::updateThumbnail($fname,self::THUMB_W,0).
					'" />'.
				'</a>';
			}
		
		return $out;
		}
	
	private function drawHTML($out){
		return '<html>'.
			'<head>'.
'<style type="text/css">
img{
	padding: 0;
	margin: 5px;
	vertical-align: top;
	border: 0;
	}
</style>'.
			'</head>'.
			'<body>'.
				$out.
			'</body>'.
			'</html>';
		}
	}

?>
