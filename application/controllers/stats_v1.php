<?php

require_once 'auth_v1.php';

class Stats_v1 extends CI_Controller {
	const FILTER = 'htmlspecialchars';
	
	// javascript files to load in view
	private $js_assets = array(
		array('http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js'),
		array('jquery.cookie.js'),
		array('ext/bootstrap.js'),
	);

	// css files to load in view
	private $css_assets = array(
		array('resources/css/ext-all.css'),
		array('resources/css/ext-all-gray.css'),
		array('cs50help.css'),
	);
	
	// urls requiring user to be logged in
	private $login_restricted = array();
	// urls restricted to current student and staff
	private $current_login_restricted = array();
	// urls restricted only to staff
	private $staff_restricted = array('view');

	public function __construct() {
		parent::__construct();
		$this->load->model('Question_v1');

		// load assets
		$this->carabiner->css($this->css_assets);
		$this->carabiner->js($this->js_assets);

		// make sure user is authenticated if need be
		//Auth_v1::authenticate($this->uri->segment(1), $this->uri->segment(3), $this->input->post('student_id'), 
		//	$this->login_restricted, $this->current_login_restricted, $this->staff_restricted);

		// send course url to view
		$course = $this->uri->segment(1);
		$this->template->set('course', $course);

		// get user information from session
		session_start();
		$user = $_SESSION[$course . '_user'];
		session_write_close();
	}

	public function view($course, $date) {
		// use current date or parse given date
		if (!$date)
			$date = strtotime('now');
		else
			$date = strtotime($date);

		$start_date = date('Y-m-d h:i:s', $date);
		$end_date = date('Y-m-d h:i:s', strtotime('+1 week', $date));

		$this->template->set('start_date', $start_date);
		$this->template->set('end_date', $end_date);
		$this->template->render();
	}
}

?>
