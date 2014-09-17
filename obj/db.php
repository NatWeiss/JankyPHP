<?php

class db{
	function __construct($user,$pass,$host,$db){
		$this->user = $user;
		$this->pass = $pass;
		$this->host = $host;
		$this->db = $db;
		$this->link = NULL;
		}
	
	function __destruct(){
		if ($this->link)
			mysql_close($this->link);
		}
	
	function next($resource){
		return mysql_fetch_assoc($resource);
		}
	
	function get($table,$key = '',$value = '',$fetch = true){
		// get this table's key's value
		$sql = "SELECT * FROM `".$this->db."`.`$table`";
		if (!empty($key) && !empty($value)){
			$sql .= " WHERE `$key` = '$value'";
			}
		$ret = $this->sql($sql);
		if($ret and $fetch)
			$ret = $this->next($ret);
		return $ret;
		}
	
	function getAll($table,$orderKey = '',$orderValue = 'ASC'){
		$sql = "SELECT * FROM `".$this->db."`.`$table`".
			((strlen($orderKey) and strlen($orderValue)) ? " ORDER BY `$orderKey` $orderValue" : '');
		return $this->sql($sql);
		}
	
	function has($table,$key,$value){
		// return true if the value exists and is not empty
		$ret = $this->get($table,$key,$value);
		$ret = ((!$ret or (is_array($ret) and empty($ret))) ? false : true);
		return $ret;
		}

	function set($table,$getKey,$getValue,$setKey,$setValue){
		return $this->update($table,$getKey,$getValue,array($setKey => $setValue));
		}

	function update($table,$whereKey,$whereValue,$values){
		unset($values[$whereKey]);
		$setSql = $this->updateValues($values);		
		$sql = "UPDATE `".$this->db."`.`$table` SET $setSql WHERE `$table`.`$whereKey` = '$whereValue';";
		return $this->sql($sql);
		}
	
	function insert($table,$values){
		$sql = $this->insertValues($values);
		$sql = "INSERT INTO `".$this->db."`.`$table` $sql;";
		return $this->sql($sql);
		}
	
	/*private*/ function sql($sqlFmt,$ra = array()){
		$ret = false;

		// connect to server
		if (!$this->link) {
			$this->link = mysql_pconnect($this->host,$this->user,$this->pass);

			// select database
			if ($this->link) {
				if(mysql_select_db($this->db, $this->link)){
					mysql_query("SET NAMES 'utf8'", $this->link);
				} else {
					mysql_close($this->link);
					$this->link = NULL;
				}
			}
		}

		// query
		if($this->link){
			// protect data
			foreach($ra as $k => $i)
				$ra[$k] = mysql_real_escape_string($i, $this->link);
			
			// print variables into sql statement
			$sql = vsprintf($sqlFmt,$ra);
			
			// execute
			//funx::debug("Executing SQL: |$sql|");
			$ret = mysql_query($sql, $this->link);

			// error?
			if(!$ret){
				$msg = "DB Error: ".mysql_error($this->link)."\nExecuting SQL: $sql\n"; 
				if (funx::isCommandline()) {
					echo $msg;
				} else {
					funx::debug($msg);
					email::send(ini::get('email-address'),'DB Error',$msg);
				}
			}
		} else {
			echo "Database connection couldn't be established\n";
		}

		return $ret;
		}

	static function datetime($time = 0){
		if(!$time)
			$time = time();
		return date('Y-m-d H:i:s',$time);
		}

	static function trim($ra,$allowedKeys){
		$ret = array();
		if(is_array($ra)){
			// copy over allowed keys
			foreach($allowedKeys as $k)
				$ret[$k] = $ra[$k];
			
			// track missing keys
			$missing = '';
			$ak = array_flip($allowedKeys);
			foreach($ra as $k => $i){
				if(!isset($ak[$k]))
					$missing .= (strlen($missing) ? ', ' : '')."$k";
				}
			if(strlen($missing)){
				$missing = "DB Warning: These fields aren't allowed in one of the tables: $missing\n";
				funx::debug($missing);
				email::send(ini::get('email-address'),'DB Squeak',$missing);
				}
			}
		return $ret;
		}

	private function updateValues($values){
		if(!is_array($values))
			$values = (array)$values;
		
		$sql = '';
		foreach($values as $k => $i ){
			if( strlen($k) and strlen($i) )
				$sql .= (strlen($sql) ? ', ' : '').
					"`$k` = ".($i === null ? "NULL" : "_utf8'$i'");
			}
		
		return $sql;
		}

	function insertValues($values){
		if(!is_array($values))
			$values = (array)$values;
		
		$kSql = '';
		$iSql = '';
		foreach($values as $k => $i ){
			if( strlen($k) and strlen($i) ){
				$kSql .= (strlen($kSql) ? ', ' : '')."`$k`";
				$iSql .= (strlen($iSql) ? ', ' : '').($i === null ? "NULL" : "_utf8'$i'");
				}
			}
		
		return "($kSql) VALUES ($iSql)";
		}
	}

?>
