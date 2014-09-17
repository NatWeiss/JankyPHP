<?php

if (!defined('DIR_KIT'))
	define('DIR_KIT', dirname(__FILE__) . '/');
include_once(DIR_KIT . 'kit.php');
	
if (strtolower(php_uname('s')) == 'darwin'){
	define('IS_DEV', true);
} else {
	umask(0022);
}

putenv("TZ=America/Los_Angeles");
define('DEBUG_LOG', DIR_KIT . 'debug.log');
ini::set('email-name', 'My Email Name');
ini::set('email-address', 'myemailaddress@mycompany.com');
ini::set('email-address-errors', 'errors@mycompany.com');

?>
