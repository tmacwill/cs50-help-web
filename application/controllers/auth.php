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
		else {
			$return_to = self::RETURN_TO;
			if ($_REQUEST['format'])
				$return_to .= '?format=' . $_REQUEST['format'];
			redirect(CS50::getLoginUrl(self::STATE, self::TRUST_ROOT, $return_to));
		}
	}

	function logout() {
		session_start();
		if (isset($_SESSION['user']))
			unset($_SESSION['user']);
		redirect('/');
	}

	function return_to() {
		$user = CS50::getUser(self::STATE, self::RETURN_TO);
		if ($user !== false)
			$_SESSION['user'] = $user;

		$identity = substr($user['identity'], strlen('https://id.cs50.net/'));
		$name = $user['fullname'];
		
		if ($_REQUEST['format'] == 'json') {
			echo json_encode(array(
				'user' => array(
					'identity' => $identity,
					'name' => $name,
				)
			));
		}

		else if ($_REQUEST['format'] == 'ipad') 
			header('Location: cs50help://user/' . $identity . '/' . $name . '/' . session_id());

		else
			redirect('questions/index');
	}
}

?>
