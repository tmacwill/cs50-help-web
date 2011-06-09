<?php

class Questions extends CI_Controller {
	const FILTER = 'htmlspecialchars';
	
	// javascript files to load in view
	private $js_assets = array(
		array('http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js'),
		array('jquery.cookie.js'),
	);

	public function __construct() {
		parent::__construct();
		$this->load->model('Question');

		// load assets
		$this->carabiner->js($this->js_assets);

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
	}

	/**
	 * Main page for student help
	 *
	 */
	public function index() {
		$this->carabiner->js('main.js');
		$this->template->render();
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
		$this->Question->dispatch($this->input->post('id'), $this->input->post('tf'));
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
	 * Log the student in.
	 * TODO: CS50 ID
	 *
	 */
	public function login() {
		if ($this->input->post()) {
			setcookie('cs50help_auth', $this->input->post('name'));
			redirect('questions/index');
		}
		else
			$this->template->render();
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
