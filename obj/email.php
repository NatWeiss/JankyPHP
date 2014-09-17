<?php

class email{
 	const NL = "\n";
 	const BOUNDARY = '4k2i9t4967296';
	const ALT_BOUNDARY = '6927694t9i2k4';
	
	function __construct($to = '',$subject = '',$msg = ''){
		if(strlen($to))
			$this->setTo($to);
		if(strlen($subject))
			$this->setSubject($subject);
		if(strlen($msg))
			$this->setBody($msg);
		}

//
// set functions
//
	function setSubject($subject){
		$this->subject = $subject;
		}

	function setBody($msg){
	 	// automatically detect HTML
		if((strstr($msg,'<') and strstr($msg,'</'))
		or stristr($msg,'<br')){
			$this->html = $msg;
			$this->plain = self::html2text($msg);
			}
		else
			$this->plain = $msg;
		}

	function setFrom($email,$name = '',$errors = ''){
		if(is_array($email)){
		 	$errors = $email['errors'];
		 	$name = $email['name'];
		 	$email = $email['email'];
			}

		$this->fromEmail = strtolower($email);
		$this->fromName = $name;
		$this->errorEmail = strtolower($errors);
		}

	function setTo($email,$name = ''){
		if(is_array($email)){
		 	$name = $email['name'];
		 	$email = $email['email'];
			}

		$this->toEmail = strtolower($email);
		$this->toName = $name;
		}

	function setExtraHeaders($ex){
	 	if(strlen($ex)){
			$this->extraHeaders = $ex.
				(substr($ex,-1) == self::NL ? '' : self::NL);
			}
		}

	function attach($file,$name = ''){
		// is it a file or pure data?
		if(file_exists($file)){
			$this->attach = file_get_contents($file);
			$this->attachName = (strlen($name) ? $name : basename($file));
			}
		elseif(strlen($file) and strlen($name)){
			$this->attach = $file;
			$this->attachName = $name;
			}
		}

//
// build an email
//
	function build(){
		$encoding = "Content-Transfer-Encoding: 7bit".self::NL.self::NL;
	 	
		// extra headers
		$h = "X-Mailer: php/".__METHOD__.self::NL;
		$h .= $this->extraHeaders;

		// from
		if(strlen($this->fromEmail)){
			if(!stristr($this->toEmail,'aol.com')
			and strlen($this->fromName))
				$h .= "From: \"$this->fromName\" <$this->fromEmail>".self::NL;
			$h .= "Reply-To: $this->fromEmail".self::NL;
			//$h .= "Return-Path: $this->fromEmail".self::NL;
			}
		if(strlen($this->errorEmail))
			$h .= "Errors-To: $this->errorEmail".self::NL;

		// plain header
		if(!strlen($this->html) and !strlen($this->attach))
			$h .= "Content-type: text/plain; charset=\"UTF-8\"".self::NL;
		// mime header
		else{
			$mime = true;
			$h .= "Mime-Version: 1.0".self::NL.
				'Content-Type: multipart/mixed; boundary="'.self::BOUNDARY.'"'.self::NL;
			}
		$h .= $encoding;

		// initial mime header
		if($mime){
			$h .= "If you are reading this, consider upgrading to a MIME-compatible E-mail program.".self::NL.
				'--'.self::BOUNDARY.self::NL;

			// split into plain / html sections
			$alt = (strlen($this->plain) and strlen($this->html));
			if($alt)
				$h .= 'Content-Type: multipart/alternative; boundary="'.
					self::ALT_BOUNDARY.'"'.self::NL.$encoding.
						'--'.self::ALT_BOUNDARY.self::NL;
			}

		// plain text
		if(strlen($this->plain)){
			if($mime)
				$h .= "Content-Type: text/plain; charset=\"UTF-8\"".self::NL.$encoding;
			$h .= $this->plain.self::NL;
			}

		// html
		if(strlen($this->html)){
			// boundary
			if($mime and strlen($this->plain))
				$h .= self::NL.'--'.($alt ? self::ALT_BOUNDARY : self::BOUNDARY).self::NL;

			if($mime)
				$h .= "Content-Type: text/html; charset=\"UTF-8\"".self::NL.$encoding;
			$h .= $this->html.self::NL;

			// end alternate boundary
			if($alt)
				$h .= self::NL.'--'.self::ALT_BOUNDARY.'--'.self::NL;
			}

		// attach
		if($mime and strlen($this->attach)){
			// boundary
			if(strlen($this->plain) or strlen($this->html))
				$h .= self::NL.'--'.self::BOUNDARY.self::NL;
			$h .= "Content-Type: application/octet-stream; name=\"$this->attachName\"".self::NL.
				  "Content-Transfer-Encoding: Base64".self::NL.
				  "Content-Disposition: attachment; filename=\"$this->attachName\"".self::NL.self::NL;

			$h .= base64_encode($this->attach).self::NL;
			}

		// end boundary
		if($mime)
			$h .= self::NL.'--'.self::BOUNDARY.'--'.self::NL;
		
		return $h;
		}

	function mail($testOnly = false){
		if(!strlen($this->toEmail)){
			funx::debug(__METHOD__." 'to' email not set");
			return false;
			}

		// build message
		$h = $this->build();

		// send
		$ret = true;
		if($testOnly)
			$result = 'WOULD send';
		else{
			$ret = @mail($this->toEmail,$this->subject,'',$h,'-f'.$this->fromEmail);
			$result = ($ret ? 'sent' : 'FAILED to send');
			}
		funx::debug(__METHOD__." $result to <$this->toEmail>: ".
			substr(str_replace("\n",'\n',$this->plain),0,256).'...');

		return $ret;
		}
//
// static functionality
//
	static function send($to,$subject,$msg,$attach='',$attachFilename=''){
		$e = new self;
		$e->setFrom(ini::get('email-address'),ini::get('email-name'));
		$e->setTo($to);
		$e->setSubject($subject);
		$e->setBody($msg);
		if(strlen($attach))
			$e->attach($attach,strlen($attachFilename) ? $attachFilename : 'attachment-1');
		return $e->mail();
		}
	
	static function parse($fname,$args){
		$msg = explode("\n",funx::inc($fname,$args));
		$subject = trim(array_shift($msg));
		$body = ra::condense($msg,'<br />');
		
		return array($subject,$body);
		}

	static function html2text($html){
		$width = 70;
		$search = array(
			"/\r/",  // Non-legal carriage return
			"/[\n\t]+/", // Newlines and tabs
			'/<script[^>]*>.*?<\/script>/i', // <script>s -- which strip_tags supposedly has problems with
			'/<style[^>]*>.*?<\/style>/i', // style tags
			'/<title[^>]*>.*?<\/title>/i', // title
			//'/<!-- .* -->/', // Comments -- which strip_tags might have problem a with
			'/<h[123][^>]*>(.+?)<\/h[123]>/ie',  // H1 - H3
			'/<h[456][^>]*>(.+?)<\/h[456]>/ie',  // H4 - H6
			'/<p[^>]*>/i',   // <P>
			'/<br[^>]*>/i',  // <br>
			'/<img[^>]*>/i',  // <img>
			'/<b[^>]*>(.+?)<\/b>/i',// <b>
			'/<i[^>]*>(.+?)<\/i>/i', // <i>
			'/<a href="([^"]+)"[^>]*>(.+?)<\/a>/ie', // <a href="">
			'/<hr[^>]*>/i',  // <hr>
			'/(<table[^>]*>|<\/table>)/i',   // <table> and </table>
			'/(<tr[^>]*>|<\/tr>)/i', // <tr> and </tr>
			'/<td[^>]*>(.+?)<\/td>/i',   // <td> and </td>
			'/&nbsp;/i',
			'/&quot;/i',
			'/&gt;/i',
			'/&lt;/i',
			'/&amp;/i',
			'/&copy;/i',
			'/&trade;/i',
			'/&#8220;/',
			'/&#8221;/',
			'/&#8211;/',
			'/&#8217;/',
			'/&#38;/',
			'/&#169;/',
			'/&#8482;/',
			'/&#151;/',
			'/&#147;/',
			'/&#148;/',
			'/&#149;/',
			'/&reg;/i'
			);
		$replace = array(
			'', // Non-legal carriage return
			'',// Newlines and tabs
			'', // <script>s -- which strip_tags supposedly has problems with
			'', // <style> tags
			'', // <title> tag
			//'', // Comments -- which strip_tags might have problem a with
			"strtoupper(\"\n\n\\1\n\n\")",  // H1 - H3
			"ucwords(\"\n\n\\1\n\n\")", // H4 - H6
			"\n\n\t", // <P>
			"\n", // <br>
			'', // <img>
			'\\1', // <b>
			'/\\1/', // <i>
			'eval(\'$a = "\\1"; $b = "\\2"; if(!strlen($b) or $a==$b) return "$b"; $j=$links[$a]; $i=(strlen($j) ? $j : count($links)+1); $links[$a]=$i; return "$b"."[$i]";\')', // <a href="">
			"\n-------------------------\n",// <hr>
			"", // <table> and </table>
			"\n",   // <tr> and </tr>
			"\\1 ",// <td> and </td>
			' ',
			'"',
			'>',
			'<',
			'&',
			'(c)',
			'(tm)',
			'"',
			'"',
			'-',
			"'",
			'&',
			'(c)',
			'(tm)',
			'--',
			'"',
			'"',
			'*',
			'(R)'
			);
		
		// run the search-and-replace
		$links = array();
		$text = trim(stripslashes($html));
		$text = preg_replace($search,$replace,$text);

		$li = new li;
		$text = $li->scan($text);

		// strip any other tags
		$text = trim(strip_tags($text));

		// bring down number of empty lines to 2 max
		//$text = preg_replace("/\n[[:space:]]+\n/", "\n", $text);
		//$text = preg_replace("/[\n]{3,}/", "\n\n", $text);

		// add link list
		foreach($links as $url => $txt)
			$linkTxt .= "[$txt] $url\n";
		if(strlen($linkTxt))
			$text .= "\n\nLinks:\n------\n$linkTxt";

		// wrap the text to a readable format
		// for PHP versions >= 4.0.2. Default width is 75
		//$text = wordwrap($text, $width);

		return $text;
		}
	}

class li{
	var $num,$mode,$indent,$prev;

	function cur(){
		$this->num++;
		return ($this->mode == 'ol' ? "$this->num) " : '* ');
		}

	function start($mode){
		$this->prev[] = $this;
		$this->num=0;
		$this->mode=$mode;
		$this->indent.="\t";
		}

	function end(){
		if(count($this->prev)){
			$t = array_pop($this->prev);
			foreach($t as $k=>$i)
			$this->$k = $i;
			}
		}

	function scan($s){
		$max = strlen($s);
		for($i=0; $i<$max; $i++){
			$code = substr($s,$i,4);
			switch($code){
				case '<ol>': $this->start('ol'); $i+=3; break;
				case '<ul>': $this->start('ul'); $i+=3; break;
				case '<li>': $ret .= $this->cur(); $i+=3; break;
				default:
					$ecode = substr($s,$i,5);
					if($ecode == '</ul>' or $ecode == '</ol>'){
						$this->end(); $i+=4;
						}
					elseif($s[$i] == "\n")
						$ret .= "\n$this->indent";
					else
						$ret .= strval($s[$i]);
				break;
				}
			}
		return $ret;
		}
	}

?>
