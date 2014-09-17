<?php

ini_set('memory_limit', '256M');
if (!defined('DIR_KIT'))
	define('DIR_KIT', dirname(__FILE__) . '/');
append_include_path(DIR_KIT);
append_include_path(DIR_KIT . 'ext/');
umask(0007);

ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);
ini::set('debug', false);
ini::set('dir-sessions', '/tmp/');

function __autoload($class) {
	$dirs = array(
		DIR_KIT,
		DIR_KIT . 'obj/',
		'php/obj/',
		);
	foreach ($dirs as $dir){
		$f = $dir . $class . '.php';
		if (is_file($f)) {
			require_once($f);
			break;
		}
	}
}

function append_include_path($dir) {
	$t = 'include_path';
	$d = ini_get($t);
	if (!strstr($d, $dir))
		ini_set($t, $d . ':' . $dir);
	//echo "Include path now: " . ini_get($t). "\n";
}

?>
