<?php

// TODO:
// recognize Content-Transfer-Encoding: base64 and Content-Type: text/plain combination

class mailbox{
	function __construct(
		$mailbox, // mailbox to parse
		$mailboxSource = '', // mailbox to copy from and truncate (optional)
		$mailboxSaved = '' // mailbox to save parsed messages (optional)
	){
		// start
		$this->timeStart = microtime(true);
		$this->mailbox = $mailbox;
		$this->mailboxSaved = $mailboxSaved;

		// copy and truncate source mailbox
		if(strlen($mailboxSource)){
			if(file_exists($mailboxSource)){
				// lock mailbox while we copy and truncate source
				$cmd = "cat $mailboxSource >> $mailbox".
					   " && echo -n '' > $mailboxSource";
				if(file_exists('/usr/sbin/exim_lock'))
					$cmd = "/usr/sbin/exim_lock $mailboxSource ".'"'.$cmd.'"';
				elseif(file_exists('/usr/sbin/postlock'))
					$cmd = "/usr/sbin/postlock $mailboxSource ".$cmd;
				$this->log("Executing '$cmd'...");
				$ret = exec($cmd,$res);
				}
			else
				$this->log("Source mailbox not found: '$mailboxSource'");
			}

		// read mailbox
		$this->data = @file_get_contents($this->mailbox);
		if(!strlen($this->data))
			$this->log("No data in mailbox '$this->mailbox'");

		return (strlen($this->data) ? true : false);
		}

	function __destruct(){
		// report
		$this->log("Messages: ".(int)$this->msgCount);
		$this->log(sprintf('Time: %.2f',microtime(true) - $this->timeStart));
		}

	function save(){
		file_put_contents($this->mailbox,$this->data);
		}

	private function saveMsg($txt){	
		if(strlen($this->mailboxSaved) and strlen($txt))
			file_put_contents($this->mailboxSaved,$txt,FILE_APPEND);
		}

	// get fields from next message
	function cutNextMsg($fields){
		$ret = false;

		// get fields
		$msg = $this->cutMsg();
		if(is_array($msg))
			foreach($fields as $key){
				switch($key){
					case 'Plain':
						$bodies = self::getBodiesByType($msg,'!attachment');
						$ret[$key] = self::bodies2plain($bodies,$msg['type']);
					break;
	
					case 'Attachments':
						$bodies = self::getBodiesByType($msg,'attachment');
						foreach($bodies as $b)
							$ret[$key][$b['filename']] = $b['body'];
					break;
	
					case 'From-Email':
						$ret[$key] = self::from2email($msg['headers']['From']);
					break;
	
					default: // 'Header'
						if(isset($msg['headers'][$key]))
							$ret[$key] = $msg['headers'][$key];
					break;
					}
				}

		return $ret;
		}

	// get message at current location and truncate data
	private function cutMsg(){
		$ret = false;

		// find message pos
		$bracket = "\n\nFrom ";
		$pos = strpos($this->data,$bracket);

		// this is the last message, use up remaining data
		if($pos === false){
			$txt = $this->data;
			$this->data = '';
			}
		// use up from here to next bracket
		else{
			$pos += strlen("\n\n");
			$txt = substr($this->data,0,$pos);
			$this->data = substr($this->data,$pos);
			}

		if(strlen($txt)){
		 	// parse text into msg array
		 	$ret = $this->parseMsg($txt);
		 	
			// save to processed mailbox
			$this->saveMsg($txt);
			}

	 	if($ret !== false)
			$this->msgCount++;

		return $ret;
		}

	// parse a chunk of text into a message array
	private function parseMsg($txt){
	 	$ret = false;
	 	
		// separate into headers / body
		$headers = $this->extractHeaders($txt);
		if($headers !== false)
			$ret = $this->getParts($txt,$headers);

		return $ret;
		}

	// extract headers from body text
	private function extractHeaders(&$body){
		// seperate headers and body
		$pos = strpos($body,"\n\n");
		if($pos !== false){
			$headerLines = explode("\n",trim(substr($body,0,$pos)));
			$body = trim(substr($body,$pos));
			}
		if(!is_array($headerLines) and !strlen($body)){
			return false;
			}

		$split = ': ';
		// parse headers
		for($i = 0; $i < count($headerLines); $i++){
			// (skip initial From bracket)
			$line = trim($headerLines[$i]);
			if(substr($line,0,5) == 'From ')
				continue;

			// split into key / val
			$pos = strpos($line,$split);
			if($pos !== false){
				$key = substr($line,0,$pos);
				$val = substr($line,$pos+strlen($split));

				// grab more lines which go with this one
				do{
					$line = trim($headerLines[$i+1]);
					$pos = strpos($line,$split);
					if($pos === false){
						$val .= ' '.$line;
						$i++;
						}
					}
				while($pos === false and $i < count($headerLines));

				$headers[$key] = trim($val);
				}
			else
				$this->log("Bad header: $line");
			}
		unset($headerLines);

		return $headers;
		}

	private function getParts($body,$headers){
		$ret = array();

		// type
		$contentType = $headers['Content-Type'];
		$ret['type'] = (strlen($contentType) ? $contentType : 'text/plain');
		if(($pos = strpos($ret['type'],';')) !== false)
			$ret['type'] = substr($ret['type'],0,$pos);

		// parse attachment
		if(strstr($headers['Content-Disposition'],'attachment')){
			$ret['type'] = 'attachment';
			$ret['filename'] = self::getAttribute('filename',$headers['Content-Disposition']);
			switch($headers['Content-Transfer-Encoding']){
				case 'base64':
					$body = base64_decode($body);
				break;
				default:
					$this->log("Unknown attachment encoding: '".
						$headers['Content-Transfer-Encoding'].'"');
				break;
				}
			}

		// headers
		$ret['headers'] = $headers;

		// is this a multipart?
		if(strstr($contentType,'multipart')){
			// get boundary
			$boundary = '--'.self::getAttribute('boundary',$contentType);

			// get parts
			$pos = strpos($body,$boundary);
			if($pos !== false){
				$body = substr($body,$pos + strlen($boundary));

				// get till next boundary
				while(($pos = strpos($body,$boundary)) !== false){
					$part = substr($body,0,$pos);
					$body = substr($body,$pos + strlen($boundary));

					// parse this part
					$m = $this->parseMsg($part);
					if($m !== false)
						$ret['body'][] = $m;
					}
				}
			}

		// regular-ass body
		else
			$ret['body'] = $body;

		return $ret;
		}

	// parses haystack for attribute (eg boundary="abcdefg")
	private static function getAttribute($needle,$haystack){
		$ret = '';
		
		$needle .= '=';
		$haystack = str::condenseWhitespace($haystack);
		$pos = strpos($haystack,$needle);
		if($pos !== false){
			// get boundary text
			$pos += strlen($needle);
			$ret = substr($haystack,$pos);

			// strip double quotes
			if($ret[0] == '"'){
				$ret = substr($ret,1);
				if(($pos = strpos($ret,'"')) !== false)
					$ret = substr($ret,0,$pos);
				}

			// truncate after space or semi-colon
			else{
				if(($pos = strpos($ret,';')) !== false)
					$ret = substr($ret,0,$pos);
				if(($pos = strpos($ret,' ')) !== false)
					$ret = substr($ret,0,$pos);
				}
			}

		return $ret;
		}

	// search message for bodies of type 'type' or '!type'
	private static function getBodiesByType($msg,$type){
		$ret = array();

		// loop in depth
		if(is_array($msg['body'])){
			foreach($msg['body'] as $m)
				$ret = array_merge($ret,self::getBodiesByType($m,$type));
			}

		// is this body of type type?
		elseif($msg['type'] == $type
		or ($type[0] == '!' and $msg['type'] != substr($type,1))){
			$m['type'] = $msg['type'];
			$m['body'] = $msg['body'];
			if($type == 'attachment')
				$m['filename'] = $msg['filename'];
			if(strlen($msg['headers']['Content-Transfer-Encoding']))
				$m['encoding'] = $msg['headers']['Content-Transfer-Encoding'];

			$ret[] = $m;
			}

		return $ret;
		}

	// condense bodies to plain text
	private static function bodies2plain($bodies,$msgType){
		$ret = '';

		// clear html if both html and plain exist
		if($msgType == 'multipart/alternative'){
			foreach($bodies as $k => $i)
				if($i['type'] == 'text/plain')
					$hasPlain = true;

			if($hasPlain)
				foreach($bodies as $k => $i)
					if($i['type'] == 'text/html')
						unset($bodies[$k]);
			}
	
		// condense bodies
		foreach($bodies as $part){
			// strip "=" at end of line
			if($part['encoding'] == 'quoted-printable')
				$part['body'] = quoted_printable_decode($part['body']);

			// convert html to plain text
			if($part['type'] == 'text/html'){
				// get html body element
				$txt = str::between($part['body'],'<body>','</body>',$pos=0,true);

				// improper html? just use the whole body part
				if(!strlen($txt))
					$txt = $part['body'];

				// clean up
				$txt = str::br2nl($txt);
				$txt = trim(str::condenseTags($txt));
				$txt = preg_replace('{[ \t]+}',' ',$txt);

				$ret .= $txt;
				}

			// already plain text
			else
				$ret .= $part['body'];
			}
		
		return $ret;
		}

	// extract pure email from "From: ..." line
	private static function from2email($str){
		// get the email between <>
		if(strstr($str,'<'))
			$str = str::between($str,'<','>',$pos=0);

		// get the email after the name
		if(($pos = strpos($str,' ')) !== false)
			$str = substr($str,0,$pos);

		$str = strtolower(trim($str));

		return $str;
		}

	// filter out the replied email from this message
	static function filterRepliedEmail($msg){
		$ret = '';
		
		// filter out everything after "On ... wrote:"
		$start = "On ";
		$end = " wrote:";
		$lines = explode("\n",$msg);
		foreach($lines as $k => $line){
			// is this the line we start filtering?
			$l = trim($line);
			if(substr($l,0,strlen($start)) == $start
			and substr($l,-strlen($end)) == $end)
				break;
			
			// add this line to return value
			if(substr($line,-1) != "\n")
				$line .= "\n";
			$ret .= $line;
			}
		
		return $ret;
		}

	private function log($txt){
		funx::debug($txt);
		}
	}
?>
