#!/usr/bin/php
<?php

define('DIR_KIT', dirname(__FILE__) . '/');
$f = DIR_KIT . 'cli-init.php';
if (is_file($f))
	include($f);

class cli{
	function __construct($argv){
		$this->cmd = new command($argv);
	}
	
	function go(){
		$method = 'do'.ucfirst($this->cmd->getArg(0));
		if(strlen($this->cmd->getArg(0))
		and method_exists($this,$method))
			$this->$method($this->cmd->getArg(1),$this->cmd->getArg(2),$this->cmd->getArg(3),$this->cmd->getArg(4),$this->cmd->getArg(5));
		else
			$this->doHelp();
		}

	function doMd5($s){
		// recommendation is to manually put the string instead of passing it by argument
		// so that it does not get stashed in bash history or anything else
		echo md5($s)."\n";
		}
	}

$cli = new cli($argv);
$cli->go();

?>
