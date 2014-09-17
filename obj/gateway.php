<?php

class gateway{
	function __construct($key){
		// include usaepay's handy php api
		require_once(DIR_KIT.'ext/usaepay.php');

		// create the transaction
		$this->tran = new umTransaction;
		$this->tran->key = $key;
		$this->tran->ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
			? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
		$this->tran->pin = '4294967296';
		//$this->tran->testmode = true;
		}

	function setCard($num,$mon,$year,$cvv2 = ''){
		$this->tran->card = $num;
		$this->tran->exp =
			str_pad($mon,2,'0',STR_PAD_LEFT).
			str_pad(substr($year,-2),2,'0',STR_PAD_LEFT);
		if(strlen($cvv2))
			$this->tran->cvv2 = $cvv2;
		}

	function setPerson($id,$name,$addy,$zip,$email = ''){
		$name = ucwords(strtolower(str::condenseNonXML($name)));
		$this->tran->cardholder = $name;
		$this->tran->street = $addy;
		$this->tran->zip = $zip;
		$this->tran->custemail = $email;
		$this->tran->invoice = $id;
		//$this->tran->custid = $id;
		$this->tran->billfname = $name;
		//$this->tran->billlname = "#$id";
		}

	function setCharge($charge,$tax = 0){
		$this->tran->amount = $charge;
		$this->tran->description = 'onetime';
		}

	function setRecurring($amt,$start,$cycle,$note=''){
		$this->tran->command = 'authonly';
		$this->tran->description = 'recurring';
		$this->tran->recurring = 'yes';
		$this->tran->schedule = ($cycle === 'Y' ? 'annually' : 'monthly');
		$this->tran->numleft = '*';
		$this->tran->start = $start;
		$this->tran->billamount = $amt;
		$this->tran->amount = $amt;
		}

	function go(){
		// transact
		set_time_limit(0);
		$ret = $this->tran->Process();

		$this->log($ret);

		return $ret;
		}

	private function log($ret){
		// safely logg this transaction
		$t = clone $this->tran;
		$t->card = substr($t->card,-4);
		unset($t->pin,$t->expir,$t->cvv2,$t->key);
		foreach($t as $k => $i){
			if(!strlen($i))
				unset($t->$k);
			else
				$t->$k = str_replace(array("\n","\r"),array('\n','\r'),$i);
			}
		
		funx::debug($t,DIR_KIT.'log/gateway.log');
		funx::debug("$t->result: \$$t->amount ************$t->card $t->invoice");
		}

	function getError(){
		return $this->tran->error;
		}
	}
?>
