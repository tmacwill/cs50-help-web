<?php

class Questions extends CI_Controller {
	const FILTER = 'htmlspecialchars';
	const AUTH_COOKIE = 'cs50help_auth';
	
	// javascript files to load in view
	private $js_assets = array(
		array('http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js'),
		array('jquery.cookie.js'),
		array('ext/bootstrap.js'),
	);

	private $css_assets = array(
		array('resources/css/ext-all.css'),
		array('resources/css/ext-all-gray.css'),
		array('cs50help.css'),
	);

	public function __construct() {
		parent::__construct();
		$this->load->model('Question');

		// load assets
		$this->carabiner->js($this->js_assets);
		$this->carabiner->css($this->css_assets);

		// form validator should escape all input
		$rules = array(
				array(
					'field' => Question::NAME_COLUMN,
					'rules' => self::FILTER
				),
				array(
					'field' => Question::QUESTION_COLUMN,
					'rules' => self::FILTER
				),
				array(
					'field' => Question::CATEGORY_COLUMN,
					'rules' => self::FILTER
				),
				array(
					'field' => Question::TF_COLUMN,
					'rules' => self::FILTER
				)
		);

		// run form validation
		$this->form_validation->set_rules($rules);
		$this->form_validation->run();

		// make sure user is authenticated if need be
		$this->authenticate();
	}

	/**
	 * Add a new student
	 *
	 */
	public function add() {
		$this->Question->add($this->input->post());
		echo json_encode(array(
			'success' => true,
			'id' => $this->db->insert_id()
		));
	}

	public function authenticate() {
		// array of restricted URLs
		$restricted = array('', 'index');

		// redirect user if trying to access a restricted action
		$action = $this->uri->segment(2);
		if (!isset($_COOKIE[self::AUTH_COOKIE]) && in_array($action, $restricted))
			redirect('questions/login');
			
	}

	/**
	 * Student closed their window
	 *
	 */
	public function closed() {
		$this->Question->set_state($this->input->post('id'), Question::STATE_CLOSED);
		echo json_encode(array('success' => true));
	}

	/**
	 * Dispatch a student to a TF
	 *
	 */
	public function dispatch() {
		$this->Question->dispatch(explode(',', $this->input->post('ids')), $this->input->post('tf'));
		echo json_encode(array('success' => true));
	}

	/**
	 * Get a list of dispatched students
	 *
	 */
	public function dispatched($force = false) {
		$dispatched = $this->Question->get_dispatched($force);
		if ($dispatched) {
			$dispatched['success'] = true;
			echo json_encode($dispatched);
		}
	}

	/**
	 * Student put their hand down
	 *
	 */
	public function hand_down() {
		$this->Question->set_state($this->input->post('id'), Question::STATE_HAND_DOWN);
		echo json_encode(array('success' => true));
	}

	/**
	 * Main page for student help
	 *
	 */
	public function index() {
		//$this->carabiner->js('main.js');
		$this->carabiner->js('questions/index.js');
		$this->template->render();
	}

	/**
	 * Log the student in.
	 *
	 */
	public function login() {
		if ($this->input->post()) {
			setcookie(self::AUTH_COOKIE, $this->input->post('name'));
			redirect('questions/index');
		}
		else {
			$this->carabiner->js('questions/login.js');
			$this->template->render();
		}
	}

	/**
	 * Get the queue of students
	 *
	 */
	public function queue($force= false) {
		$queue = $this->Question->get_queue($force);
		if ($queue) {
			$queue['success'] = true;
			echo json_encode($queue);
		}
	}
}

?>
