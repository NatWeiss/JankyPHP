<?php
/*
Warning: imagecreatefromstring() [function.imagecreatefromstring]: gd-jpeg: JPEG library reports unrecoverable error: in /home/nat/kit/obj/img.php on line 17

Warning: imagecreatefromstring() [function.imagecreatefromstring]: Passed data is not in 'JPEG' format in /home/nat/kit/obj/img.php on line 17

Warning: imagecreatefromstring() [function.imagecreatefromstring]: Couldn't create GD Image Stream out of Data in /home/nat/kit/obj/img.php on line 17

Warning: imagesx(): supplied argument is not a valid Image resource in /home/nat/kit/obj/img.php on line 34

Warning: imagesy(): supplied argument is not a valid Image resource in /home/nat/kit/obj/img.php on line 38

Warning: Division by zero in /home/nat/kit/obj/img.php on line 72

Warning: imagecreatetruecolor() [function.imagecreatetruecolor]: Invalid image dimensions in /home/nat/kit/obj/img.php on line 74

Warning: imagecopyresampled(): supplied argument is not a valid Image resource in /home/nat/kit/obj/img.php on line 77

Warning: imagedestroy(): supplied argument is not a valid Image resource in /home/nat/kit/obj/img.php on line 80
*/
class img{
	function __construct($x,$y = null){
		ini_set('memory_limit','128M');

		// create from dimensions
		if(is_numeric($x) and is_numeric($y)
		and $x > 0 and $y > 0)
			$this->img = imagecreatetruecolor($x,$y);
		
		// create from file data
		else{
			if(file_exists($x)){
				$path = pathinfo($x);
				$this->type = strtolower($path['extension']);
				$x = file_get_contents($x);
				}
			$this->img = imagecreatefromstring($x);
			}
		}

	function setType($type){
		$this->type = $type;
		}

	function getType(){
		$ret = $this->type;
		if(!is_callable("image$ret"))
			$ret = 'jpeg';
		
		return $ret;
		}

	function getWidth(){
		return imagesx($this->img);
		}

	function getHeight(){
		return imagesy($this->img);
		}

	function render($quality = 50){
		// save
		ob_start();
		$func = "image".$this->getType();
		$func($this->img,'',$quality);
		$ret = ob_get_contents();
		ob_end_clean();
		
		return $ret;
		}

	function fill($r,$g,$b){
		imagefill($this->img,$r,$g,$b);
		}
	
	function resizeMax($dim){
		if($this->getWidth() > $this->getHeight())
			$this->resize($dim,0);
		else
			$this->resize(0,$dim);
		}
	
	function resize($width,$height){
		// resize
		$w = $this->getWidth();
		$h = $this->getHeight();
		
		// smart adjust
		if($width == 0)
			$width = (($height / $h) * $w);
		if($height == 0)
			$height = (($width / $w) * $h);
		
		$dest = imagecreatetruecolor($width,$height);
		imagecopyresampled($dest,$this->img,
			0,0,0,0,
			$width,$height,$w,$h);
		
		// move
		imagedestroy($this->img);
		$this->img = $dest;
		}

	function crop($x1,$y1,$x2,$y2){
		// crop
		$width = $x2 - $x1;
		$height = $y2 - $y1;
		$dest = imagecreatetruecolor($width,$height);
		imagecopy($dest,$this->img,
			0,0,$x1,$y1,
			$width,$height);

		// move
		imagedestroy($this->img);
		$this->img = $dest;
		}
	
	function rotate($degrees,$bg = 0){
		$dest = imagerotate($this->img,$degrees,$bg);
		imagedestroy($this->img);
		$this->img = $dest;
		}

	function paste(img $draw,
		$dest_x,$dest_y,
		$src_x = null,$src_y = null,
		$dest_w = null,$dest_h = null,
		$src_w = null,$src_h = null
	){
		// default is to paste the image cleanly at dest_x, dest_y
		if($src_x === null){
			$src_x = $src_y = 0;
			$dest_w = $src_w = $draw->getWidth();
			$dest_h = $src_h = $draw->getHeight();
			}

		// paste the image
		imagecopyresampled($this->img,$draw->img,
			$dest_x,$dest_y,
			$src_x,$src_y,
			$dest_w,$dest_h,
			$src_w,$src_h
			);
		}

	static private $filterTypes = array(
		'brightness' => IMG_FILTER_BRIGHTNESS,
		'contrast' => IMG_FILTER_CONTRAST,
		'grayscale' => IMG_FILTER_GRAYSCALE,
		'colorize' => IMG_FILTER_COLORIZE,
		'smooth' => IMG_FILTER_SMOOTH,
		'gaussian blur' => IMG_FILTER_GAUSSIAN_BLUR,
		'selective blur' => IMG_FILTER_SELECTIVE_BLUR,
		'mean removal' => IMG_FILTER_MEAN_REMOVAL,
		'edge detect' => IMG_FILTER_EDGEDETECT,
		'emboss' => IMG_FILTER_EMBOSS,
		'negate' => IMG_FILTER_NEGATE,
		);
	
	static function getFilterTypes(){
		return self::$filterTypes;
		}

	function filter($type,$arg1,$arg2,$arg3){
		imagefilter($this->img,$type,$arg1,$arg2,$arg3);
		}

	function textSize($size,$angle,$font,$txt){
		$ret = imagettfbbox($size,$angle,$font,$txt);
		$width = abs($ret[0] - $ret[4]);
		$height = abs($ret[5] - $ret[1]);
		$ret = array($width,$height);

		return $ret;
		}

	function text($size,$angle,$x,$y,$color,$font,$txt){
		$clr = imagecolorallocate($this->img,$color[0],$color[1],$color[2]);
		return imagettftext($this->img,$size,$angle,$x,$y,$clr,$font,$txt);
		//return imagestring($this->img,5,$x,$y,$txt,$clr);
		}

	static function getJpegComment($fname){
		// make sure you have ~/src in your php.ini include_path
		require_once('jpeg-toolkit/JPEG.php');

		$headers = get_jpeg_header_data($fname);
		
		$ret = stripslashes(get_jpeg_Comment($headers));
		
		if(trim($ret) == 'AppleMark'){
			$ret = '';
/*			include('jpeg-toolkit/EXIF.php');
			$data = get_EXIF_JPEG($fname);
			funx::decho($headers);
			echo Interpret_EXIF_to_HTML($data,$fname);
*/			}

		return $ret;
		}

	static function setJpegComment($fname,$comment){
		require_once('jpeg-toolkit/JPEG.php');

		$headers = get_jpeg_header_data($fname);
		$headers = put_jpeg_Comment($headers,$comment);
		put_jpeg_header_data($fname,$fname,$headers);
		}
	
	static function updateThumbnail($fname,$w,$h,$quality = 25){
		$thumbDir = dirname($fname).'/.thumbs/';
		$thumbFname = $thumbDir.basename($fname);
		$doUpdate = (@filectime($fname) > @filectime($thumbFname));
		
		if($doUpdate){
			// create thumbs dir
			if(!file_exists($thumbDir))
				@mkdir($thumbDir);
			
			// save thumbnail
			$img = new img($fname);
			$img->resize($w,$h);
			file_put_contents($thumbFname,$img->render($quality));
			}
		
		return $thumbFname;
		}
	}

?>
