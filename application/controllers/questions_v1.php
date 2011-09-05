<?php

require_once 'auth_v1.php';

class Questions_v1 extends CI_Controller {
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
	private $login_restricted = array('', 'add', 'can_ask', 'closed', 'dispatch', 'dispatched', 'hand_down', 'index', 'q', 'queue');
	// urls restricted to current student and staff
	private $current_login_restricted = array('add', 'closed', 'hand_down');
	// urls restricted only to staff
	private $staff_restricted = array('dispatch');

	public function __construct() {
		parent::__construct();
		$this->load->model('Question_v1');

		// load assets
		$this->carabiner->css($this->css_assets);
		$this->carabiner->js($this->js_assets);

		// form validator should escape all input
		$rules = array(
				array(
					'field' => Question_v1::NAME_COLUMN,
					'rules' => self::FILTER
				),
				array(
					'field' => Question_v1::QUESTION_COLUMN,
					'rules' => self::FILTER
				),
				array(
					'field' => Question_v1::CATEGORY_COLUMN,
					'rules' => self::FILTER
				),
				array(
					'field' => Question_v1::TF_COLUMN,
					'rules' => self::FILTER
				)
		);

		// run form validation
		$this->form_validation->set_rules($rules);
		$this->form_validation->run();

		// send course url to view
		$course = $this->uri->segment(1);
		if (empty($course))
			$course = 'cs50';
		$this->template->set('course', $course);

		// make sure user is authenticated if need be
		Auth_v1::authenticate($course, $this->uri->segment(3), $this->input->post('student_id'), $this->login_restricted, $this->current_login_restricted, $this->staff_restricted);

		// get user information from session
		session_start();
		$user = isset($_SESSION[$course . '_user']) ? $_SESSION[$course . '_user'] : null;
		session_write_close();

		// make sure user exists
		if (!$user) {
			redirect("/{$course}/auth/login");
			exit();
		}
		
		// send user information to view
		if (isset($user['identity']))
			$this->template->set('identity', substr($user['identity'], strlen('https://id.cs50.net/')));
		if (isset($user['name']))
			$this->template->set('name', $user['name']);
	}

	/**
	 * Add a new question
	 *
	 */
	public function add($course) {
		if ($this->Question_v1->add($this->input->post(), $course)) {
			echo json_encode(array(
				'success' => true,
				'id' => $this->db->insert_id()
			));
		}
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Check if OHs are in session, and therefore accepting new questions
	 *
	 */
	public function can_ask($course) {
		echo json_encode(array('can_ask' => $this->Question_v1->can_ask($course)));
	}

	/**
	 * Student closed their window
	 *
	 */
	public function closed($course) {
		if ($this->Question_v1->set_state($this->input->post('id'), Question_v1::STATE_CLOSED, $course))
			echo json_encode(array('success' => true));
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Dispatch a student to a TF
	 *
	 */
	public function dispatch($course) {
		if ($this->Question_v1->dispatch(explode(',', $this->input->post('ids')), $this->input->post('tf'), $course))
			echo json_encode(array('success' => true));
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Get a list of dispatched students
	 *
	 */
	public function dispatched($course, $force = false) {
		$dispatched = $this->Question_v1->get_dispatched($course, $force);
		if ($dispatched) {
			$dispatched['success'] = true;
			echo json_encode($dispatched);
		}
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Student put their hand down
	 *
	 */
	public function hand_down($course) {
		if ($this->Question_v1->set_state($this->input->post('id'), Question_v1::STATE_HAND_DOWN, $course))
			echo json_encode(array('success' => true));
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Student made their quesiton invisible
	 *
	 */
	public function invisible($course) {
		if ($this->Question_v1->set_show($this->input->post('id'), 0, $course))
			echo json_encode(array('success' => true));
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Main page for student help
	 *
	 */
	public function index() {
		$this->carabiner->js('questions/index.js');
		$this->template->render();
	}

	/**
	 * Student has just logged in
	 *
	 */
	public function login($course) {
		if ($this->Question_v1->login($this->input->post('student_id'), $course))
			echo json_encode(array('success' => true));
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Main page for student help (alias for index)
	 *
	 */
	public function q() {
		$this->template->current_view = 'questions_v1/index';
		$this->carabiner->js('questions/index.js');
		$this->template->render();
	}

	/**
	 * Get the queue of students
	 *
	 */
	public function queue($course, $force = false) {
		$queue = $this->Question_v1->get_queue($course, $force);
		if ($queue) {
			$queue['success'] = true;
			echo json_encode($queue);
		}
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Student made their quesiton visible
	 *
	 */
	public function visible($course) {
		if ($this->Question_v1->set_show($this->input->post('id'), 1, $course))
			echo json_encode(array('success' => true));
		else
			echo json_encode(array('success' => false));
	}
}

?>
