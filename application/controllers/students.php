<?php

class Students extends CI_Controller {
	const FILTER = 'htmlspecialchars';

	public function __construct() {
		parent::__construct();
		$this->load->model('Student');

		// form validator should escape all input
		$rules = array(
			array(
				'field' => Student::NAME_COLUMN,
				'rules' => self::FILTER
			),
			array(
				'field' => Student::QUESTION_COLUMN,
				'rules' => self::FILTER
			),
			array(
				'field' => Student::CATEGORY_COLUMN,
				'rules' => self::FILTER
			),
			array(
				'field' => Student::TF_COLUMN,
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
		$this->template->render();
	}

	/**
	 * Add a new student
	 *
	 */
	public function add() {
		$this->Student->add($this->input->post());
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
		$this->Student->set_state($this->input->post('id'), Student::STATE_CLOSED);
		echo json_encode(array('success' => true));
	}

	/**
	 * Dispatch a student to a TF
	 *
	 */
	public function dispatch() {
		$this->Student->dispatch($this->input->post('id'), $this->input->post('tf'));
		echo json_encode(array('success' => true));
	}

	/**
	 * Student put their hand down
	 *
	 */
	public function hand_down() {
		$this->Student->set_state($this->input->post('id'), Student::STATE_HAND_DOWN);
		echo json_encode(array('success' => true));
	}

	/**
	 * Student put their hand up
	 * Should NOT be called for a new question, but exists in case of corner case
	 *
	 */
	public function hand_up() {
		$this->Student->set_state($this->input->post('id'), Student::STATE_HAND_UP);
		echo json_encode(array('success' => true));
	}

	/**
	 * Student has been helped
	 *
	 */
	public function help() {
		$this->Student->set_state($this->input->post('id'), Student::STATE_HELPED);
		echo json_encode(array('success' => true));
	}

	/**
	 * Get the position of the given student
	 *
	 */
	public function position() {
		$result = $this->Student->get_position($this->input->post('id'));
		if ($result) {
			$result['success'] = true;
			echo json_encode($result);
		}
	}

	/**
	 * Get the queue of students
	 *
	 */
	public function queue() {
		$queue = $this->Student->get_queue();
		if ($queue) {
			echo json_encode(array(
				'success' => true,
				'queue' => $queue
			));
		}
	}
}

?>
