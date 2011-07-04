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
	 * Ensure user has the required permissions to perform an action
	 *
	 */
	// @TODO: MAKE THIS SUCK LESS, WANT TO PASS SOMETHING LIKE 'LOGIN_RESTRICTED' => ARRAY('QUESTIONS/DISPATCH') AND 'CS50/QUESTIONS/DISPATCH'
	public static function authenticate($course, $action, $id, $login_restricted, $current_login_restricted, $staff_restricted) {
		session_start();
		$user = isset($_SESSION[$course . '_user']) ? $_SESSION[$course . '_user'] : false;
		$staff = isset($_SESSION[$course . '_staff']) ? $_SESSION[$course . '_staff'] : false;
		session_write_close();
		
		// permit staff to take any action
		if ($staff === false) {
			// user is not logged in or is trying to take action on behalf of another user
			if (($user === false && in_array($action, $login_restricted)) || in_array($action, $staff_restricted) ||
					(($user === false || $user['identity'] != $id) && in_array($action, $current_login_restricted))) {
				// @TODO: handle request denial better
				if (isset($_REQUEST['format']) && $_REQUEST['format'] == 'json') {
					echo json_encode(array('success' => false));
					exit;
				}
				else 
					redirect($course . '/auth/login');
			}
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
			redirect($course . '/questions/index');
		else {
			// construct return url, which includes data format and course url
			$return_to = self::RETURN_TO;
			$return_to .= '?course=' . $course;
			if ($_REQUEST['format'])
				$return_to .= '&format=' . $_REQUEST['format'];
			if ($_REQUEST['staff_required'])
				$return_to .= '&staff_required=true';

			redirect(CS50::getLoginUrl(self::STATE, self::TRUST_ROOT, $return_to, array('fullname', 'email'), array('https://id.cs50.net/schema/harvardeduidnumber')));
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
		$user = CS50::getUser(self::STATE, self::RETURN_TO);
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

	/**
	 * Verify that the given user is a staff member of a course
	 * @param $user [Array] User object
	 * @param $course [String] Course url
	 *
	 */
	private function verify_staff($user, $course) {
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

	/**
	 * Verify that the given user is a student of a course
	 * @param $user [Array] User object
	 * @param $course [String] Course url
	 *
	 */
	private function verify_student($user, $course) {
		$students = $this->Spreadsheet_v1->get_students($course);
		$is_student = false;
	
		// iterate over all staff members from spreadsheet
		foreach ($students['students'] as $s) {
			if ($user['https://id.cs50.net/schema/harvardeduidnumber'][0] == $s) {
				$is_student = true;
				break;
			}
		}

		// logged-in user is not a student of this course, so redirect back to login page
		if (!$is_student) {
			echo json_encode(array('success' => false));
			redirect($course . '/auth/login');
			return;
		}
	}
}

?>
