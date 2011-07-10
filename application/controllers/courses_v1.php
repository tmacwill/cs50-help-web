<?php

class Courses_v1 extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('Course_v1');
	}

	function all() {
		$courses = $this->Course_v1->get_courses();
		if ($courses) {
			$courses['success'] = true;
			echo json_encode($courses);
		}
		else
			echo json_encode(array('success' => false));
	}
}

?>
