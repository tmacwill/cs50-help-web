<?php

require_once(dirname(__FILE__) . '/cs50/CS50.php');

class Auth_v1 extends CI_Controller {
	const STATE = '/srv/www/tommymacwilliam.com/public_html/cs50help/application/cs50id_state';
	const TRUST_ROOT = 'http://tommymacwilliam.com/cs50help/auth/';
	const RETURN_TO = 'http://tommymacwilliam.com/cs50help/auth/return_to';

	function __construct() {
		parent::__construct();
		$this->load->model('Spreadsheet_v1');
	}

	/**
	 * Redirect the user to CS50 ID
	 *
	 */
	function login() {
		session_start();
		$course = $this->uri->segment(1);

		if (isset($_SESSION[$course . '_user']))
			redirect($course . '/questions/index');
		else {
			// construct return url, which includes data format and course url
			$return_to = self::RETURN_TO;
			$return_to .= '?course=' . $course;
			if ($_REQUEST['format'])
				$return_to .= '&format=' . $_REQUEST['format'];
			if ($_REQUEST['staff_required'])
				$return_to .= '&staff_required=true';

			redirect(CS50::getLoginUrl(self::STATE, self::TRUST_ROOT, $return_to));
		}
	}

	/**
	 * Log the user out by clearing the session
	 *
	 */
	function logout() {
		$course = $this->uri->segment(1);
		session_start();
		
		// clear user information
		if (isset($_SESSION[$course . '_user']))
			unset($_SESSION[$course . '_user']);
		// clear staff information
		if (isset($_SESSION[$course . '_staff']))
			unset($_SESSION[$course . '_staff']);

		redirect('/');
	}

	/**
	 * Store CS50 ID user information
	 *
	 */
	function return_to() {
		// get user information from CS50 ID
		$user = CS50::getUser(self::STATE, self::RETURN_TO);
		$course = $_REQUEST['course'];

		// verify user is staff if necessary
		if (isset($_REQUEST['staff_required'])) {
			$staff = $this->Spreadsheet_v1->get_staff($course);
			$is_staff = false;
			
			// iterate over all staff members from spreadsheet
			foreach ($staff['staff'] as $s) {
				if ($user['email'] == $s['email']) {
					$is_staff = true;
					break;
				}
			}

			// logged-in user is not staff, so redirect back to login page
			if (!$is_staff) {
				echo json_encode(array('success' => false));
				redirect($course . '/auth/login');
				return;
			}

			// user is staff only for this course
			else
				$_SESSION[$course . '_staff'] = true;
		}

		// successful login
		if ($user !== false) {
			// remove id URL from identity
			$identity = substr($user['identity'], strlen('https://id.cs50.net/'));
			$name = $user['fullname'];
			
			// store user info in course-specific key
			$session_user = array(
				'identity' => $identity,
				'name' => $name,
			);
			$_SESSION[$course . '_user'] = $session_user;

			// successful login, output user info
			if ($_REQUEST['format'] == 'json') {
				echo json_encode(array(
					'success' => true,
					'user' => $session_user,
				));
			}

			// ipad: redirect user to URL that will close UIWebView
			else if ($_REQUEST['format'] == 'ipad') 
				header('Location: cs50help://user/' . $identity . '/' . $name . '/' . session_id());

			// web app: redirect user to questions view
			else
				redirect($_REQUEST['course'] . '/questions/index');
		}

		// invalid user, so redirect back to login page
		else
			redirect($_REQUEST['course'] . '/auth/login');
	}
}

?>
