<?php

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
		);

		// run form validation
		$this->form_validation->set_rules($rules);
		$this->form_validation->run();

		// send course url to view
		$course = $this->uri->segment(1);
		if (empty($course))
			$course = 'cs50';
		$this->template->set('course', $course);

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
			$this->template->set('identity', $user['identity']);
		if (isset($user['name']))
			$this->template->set('name', $user['name']);
	}

	/**
	 * Add a new question
	 *
	 */
	public function add($course) {
		if (!$this->authenticate_user($course)) {
			echo json_encode(array('success' => false));
			return false;
		}

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
	 * Ensure the logged-in user is a staff member
	 * @param $course [String] Course url
	 *
	 */
	public function authenticate_staff($course) {
		session_start();
		$user = isset($_SESSION[$course . '_user']) ? $_SESSION[$course . '_user'] : false;
		$staff = isset($_SESSION[$course . '_staff']) ? $_SESSION[$course . '_staff'] : false;
		session_write_close();

		return !!$staff;
	}

	/**
	 * Ensure the logged-in user is a student and owns the object they are trying to operate on
	 * @param $course [String] Course url
	 * @param $question_id [Integer] Question ID to operate
	 *
	 */
	public function authenticate_user($course, $question_id = false) {
		session_start();
		$user = isset($_SESSION[$course . '_user']) ? $_SESSION[$course . '_user'] : false;
		$staff = isset($_SESSION[$course . '_staff']) ? $_SESSION[$course . '_staff'] : false;
		session_write_close();

		// user is not logged in, so redirect to login page
		if (!$user) {
			redirect("/{$course}/auth/login");
			exit();
		}

		// staff can do anything
		if ($staff)
			return true;

		// if not staff, make sure current student owns question
		if ($question_id)
			return $this->Question_v1->check_permission($course, $question_id);

		return true;
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
		if (!$this->authenticate_user($course, $this->input->post('id'))) {
			echo json_encode(array('success' => false));
			return false;
		}

		if ($this->Question_v1->set_state($this->input->post('id'), Question_v1::STATE_CLOSED, $course))
			echo json_encode(array('success' => true));
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Turn off the queue
	 *
	 */
	public function disable($course) {
		if (!$this->authenticate_staff($course)) {
			echo json_encode(array('success' => false));
			return false;
		}

		if ($this->Question_v1->set_queue_state($course, false))
			echo json_encode(array('success' => true, 'can_ask' => false));
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Dispatch a student to a TF
	 *
	 */
	public function dispatch($course) {
		if (!$this->authenticate_staff($course)) {
			echo json_encode(array('success' => false));
			return false;
		}

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
		if (!$this->authenticate_user($course)) {
			echo json_encode(array('success' => false));
			return false;
		}

		$dispatched = $this->Question_v1->get_dispatched($course, $force);
		if ($dispatched) {
			$dispatched['success'] = true;
			echo json_encode($dispatched);
		}
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Turn on the queue
	 *
	 */
	public function enable($course) {
		if (!$this->authenticate_staff($course)) {
			echo json_encode(array('success' => false));
			return false;
		}

		if ($this->Question_v1->set_queue_state($course, true))
			echo json_encode(array('success' => true, 'can_ask' => 'true'));
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Student put their hand down
	 *
	 */
	public function hand_down($course) {
		if (!$this->authenticate_user($course, $this->input->post('id'))) {
			echo json_encode(array('success' => false));
			return false;
		}

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
		if (!$this->authenticate_user($course, $this->input->post('id'))) {
			echo json_encode(array('success' => false));
			return false;
		}

		if ($this->Question_v1->set_show($this->input->post('id'), 0, $course))
			echo json_encode(array('success' => true));
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Get info for a single question
	 * @param $course [String] Course url
	 * @param $id [Integer] Question id
	 * @return Information for question
	 *
	 */
	public function get($course, $id) {
		if (!$this->authenticate_user($course, $this->input->post('id'))) {
			echo json_encode(array('success' => false));
			return false;
		}

		$question = $this->Question_v1->get_question($id);
		if ($question) {
			$question['success'] = true;
			echo json_encode($question);
		}
		else
			echo json_encode(array('success' => false));
	}

	/**
	 * Main page for student help
	 *
	 */
	public function index($course = '') {
		if (empty($course))
			$course = 'cs50';

		if (!$this->authenticate_user($course)) {
			echo json_encode(array('success' => false));
			return false;
		}

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
	public function q($course = '') {
		$this->template->current_view = 'questions_v1/index';
		//$this->carabiner->js('questions/index.js');
		//$this->template->render();
		return $this->index($course);
	}

	/**
	 * Get the queue of students
	 *
	 */
	public function queue($course, $force = false) {
		if (!$this->authenticate_user($course)) {
			echo json_encode(array('success' => false));
			return false;
		}

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
		if (!$this->authenticate_user($course, $this->input->post('id'))) {
			echo json_encode(array('success' => false));
			return false;
		}

		if ($this->Question_v1->set_show($this->input->post('id'), 1, $course))
			echo json_encode(array('success' => true));
		else
			echo json_encode(array('success' => false));
	}
}

?>
