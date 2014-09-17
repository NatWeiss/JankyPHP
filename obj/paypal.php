<?

class paypal{
	function __construct($user,$pass,$sig,$environment = 'live'){
		// save credentials
		$this->user = $user;
		$this->pass = $pass;
		$this->sig = $sig;
		$this->environment = $environment;

		// create endpoint
		$this->endpoint = "https://api-3t.paypal.com/nvp";
		if("sandbox" === $this->environment or "beta-sandbox" === $this->environment){
			$this->endpoint = "https://api-3t.".$this->environment.".paypal.com/nvp";
			}
		}

	function doMethod($methodName, $nvpArray){
		// create final name/value/pair array
		$nvpArray = array_merge(array(
			'METHOD' => $methodName,
			'VERSION' => '51.0',
			'PWD' => $this->pass, // paypal-friendly order
			'USER' => $this->user,
			'SIGNATURE' => $this->sig,
			),
			$nvpArray);

		// make it a urlencoded string
		$nvpStr = ra::toNVP($nvpArray);
		//funx::debug($nvpStr);
	
		// create curl
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $this->endpoint);
		//curl_setopt($c, CURLOPT_VERBOSE, 1);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($c, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_POST, 1);
		curl_setopt($c, CURLOPT_POSTFIELDS, $nvpStr);
	
		// execute method on paypal server
		$response = curl_exec($c);
		
		// parse the response
		$ret = ($response ? ra::fromNVP($response) :
			array('L_LONGMESSAGE' => 'Couldn\'t talk to payment server. Please try again later. '.curl_error($c).'('.curl_errno($c).')'));
	
		// debug message
		funx::debug("Response to $methodName: ".var_export($response,true)."\n".var_export($ret,true));
		$this->lastErrorTxt = 
			"Response was ".str_replace(array("  '"),array("<br /> &nbsp; '"),var_export($ret,true)).
			"<br /><br />".
			"NVP was ".var_export(str_replace(array("&",$this->user,$this->pass,$this->sig,urlencode($nvpArray['ACCT'])),array("<br /> &nbsp; &amp;",'','','',substr($nvpArray['ACCT'],-5,5)),$nvpStr),true).
			'';
	
		return $ret;
		}
	
	function sendLastError($txt){
		email::send(ini::get('email-address'),'Paypal Squeak',$txt.'<br /><br />'.$this->lastErrorTxt);
		}
	
	static function successful($response){
		return ("SUCCESS" == strtoupper($response["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($response["ACK"]));
		}

	static function getErrorTxt($response){
		$ret = '';
		
		// get error message
		foreach($response as $k => $i)
			if(stristr($k,'L_LONGMESSAGE'))
				$ret .= (strlen($ret) ? '<br />' : '').$i;

		// replace repetitive text
		$ret = str_replace(array('This transaction cannot be processed.'),array(''),$ret);

		// else use default message
		if(!strlen($ret))
			$ret = 'Declined or unknown error. Please try a different payment method.';

		return $ret;
		}
	}

?>
