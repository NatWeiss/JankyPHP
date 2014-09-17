<?php

class session{
	const TIME_LIMIT = 5400;

	private $users = array(
		'nat' => 'fa570c79117bd778fb1941ffbe85cd6a',
		);

	function isIn(){	
		// logout?
		if($_GET['logout'] == 1)
			$this->logout();

		// login?
		elseif(strlen($_POST['pass'])){
			if($this->users[$_POST['user']] == md5($_POST['pass']))
				$this->login($_POST['user'],$_POST['pass']);
			else
				sleep(rand(3,5));
			}
		
		// logged in?
		else
			$this->load();

		// is in?
		$this->in = (strlen($_SESSION['user'])
			and $this->users[$_SESSION['user']] == $_SESSION['pass']);
		
		return $this->in;
		}

	private function login($user,$pass){
		$this->load();
		
		// stash user / pass in session
		$_SESSION['user'] = $user;
		$_SESSION['pass'] = md5($pass);
		}
	
	private function logout(){
		$this->unload();

		// erase session
		$_SESSION = array();
		}

	function __construct(){
		session_save_path(DIR_ROOT.'sessions');
		session_set_cookie_params(self::TIME_LIMIT);
		session_start();
		if(!strlen(session_id()))
			session_regenerate_id();
		}
	
	private function load(){
		setcookie(session_name(),session_id(),time() + self::TIME_LIMIT);
		}
	
	private function unload(){
		setcookie(session_name(),'',time()-42000,'/');
		@session_destroy();
		}
	}

?>
