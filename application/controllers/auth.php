<?php

require_once(dirname(__FILE__) . '/cs50/CS50.php');

class Auth extends CI_Controller {
	const STATE = '/srv/www/tommymacwilliam.com/public_html/cs50help/application/cs50id_state';
	const TRUST_ROOT = 'http://tommymacwilliam.com/cs50help/auth/';
	const RETURN_TO = 'http://tommymacwilliam.com/cs50help/auth/return_to';

	function login() {
		session_start();
		if (isset($_SESSION['user']))
			redirect('questions/index');
		else
			redirect(CS50::getLoginUrl(self::STATE, self::TRUST_ROOT, self::RETURN_TO));
	}

	function logout() {
		session_start();
		if (isset($_SESSION['user']))
			unset($_SESSION['user']);
		redirect('/');
	}

	function return_to() {
		$user = CS50::getUser(self::STATE, self::RETURN_TO);
		session_start();
		if ($user !== false)
			$_SESSION['user'] = $user;
		
		redirect('questions/index');
	}
}

?>
