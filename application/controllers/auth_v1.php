<?php

//require_once(dirname(__FILE__) . '/cs50/CS50.php');
require_once('CS50/CS50.php');

class Auth_v1 extends CI_Controller {
	const STATE = '/var/www/html/application/cs50id_state';

	function __construct() {
		parent::__construct();
		$this->load->model('Staff_v1');

        if (getenv('SERVER') == 'DEV') {
			define('TRUST_ROOT', 'http://192.168.56.50/auth/');
			define('RETURN_TO', 'http://192.168.56.50/auth/return_to');
		}
		else {
			define('TRUST_ROOT', 'http://queue.cs50.net/auth/');
			define('RETURN_TO', 'http://queue.cs50.net/auth/return_to');  
		}
	}

	/**
	 * Redirect the user to CS50 ID
	 *
	 */
	public function login() {
		session_start();
		$course = $this->uri->segment(1);
		
		// user is already logged in, so redirect to main page
		if (isset($_SESSION[$course . '_user']))
			redirect($course . '/questions/q');
		else {
			// construct return url, which includes data format and course url
			$return_to = RETURN_TO;
			$return_to .= '?course=' . $course;
			if (isset($_REQUEST['format']))
				$return_to .= '&format=' . $_REQUEST['format'];
			if (isset($_REQUEST['staff_required']))
				$return_to .= '&staff_required=true';

			redirect(CS50::getLoginUrl(self::STATE, TRUST_ROOT, $return_to, array('fullname', 'email'), array('https://id.cs50.net/schema/harvardeduidnumber')));
		}
	}

	/**
	 * Log the user out by clearing the session
	 *
	 */
	public function logout() {
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
	public function return_to() {
		// get user information from CS50 ID
		$user = CS50::getUser(self::STATE, RETURN_TO);
		$course = $_REQUEST['course'];

		// successful login
		if ($user !== false) {
			// verify user is staff if necessary
			if (isset($_REQUEST['staff_required']))
				$this->verify_staff($user, $course);

			// verify user is a student
			else
				$this->verify_student($user, $course);

			// remove id URL from identity
			$identity = substr($user['identity'], strlen('https://id.cs50.net/'));
			$name = $user['fullname'];
			$huid = $user['https://id.cs50.net/schema/harvardeduidnumber'][0];
			
			// store user info in course-specific key
			$session_user = array(
				'identity' => $identity,
				'name' => $name,
				'huid' => ($huid) ? $huid : 0,
			);
			$_SESSION[$course . '_user'] = $session_user;

			// get desired request format
			$format = (isset($_REQUEST['format'])) ? $_REQUEST['format'] : '';

			// api: output user info
			if ($format == 'json') {
				echo json_encode(array(
					'success' => true,
					'user' => $session_user,
				));
			}

			// ipad: redirect user to URL that will close UIWebView
			else if ($format == 'ipad') 
				header('Location: cs50help://user/' . $identity . '/' . $name . '/' . session_id());

			// web app: redirect user to questions view
			else
				redirect($_REQUEST['course'] . '/questions/q');
		}

		// invalid user, so redirect back to login page
		else
			redirect($_REQUEST['course'] . '/auth/login');
	}

	/**
	 * Verify that the given user is a staff member of a course
	 * @param $user [Array] User object
	 * @param $course [String] Course url
	 *
	 */
	private function verify_staff($user, $course) {
		$staff = $this->Staff_v1->get_staff($course);
		$is_staff = false;
		
		// iterate over all staff members 
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

	/**
	 * Verify that the given user is a student of a course
	 * @param $user [Array] User object
	 * @param $course [String] Course url
	 *
	 */
	private function verify_student($user, $course) {
		// temporarily allow anyone to log in
		return true;
	}
}

?>
