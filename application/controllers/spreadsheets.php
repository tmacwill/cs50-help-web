<?php

class Spreadsheets extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('Spreadsheet');
	}

	public function categories() {
		$categories = $this->Spreadsheet->get_categories();
		if ($categories) {
			$categories['success'] = true;
			echo json_encode($categories);
		}
	}

	public function schedule() {
		$schedule = $this->Spreadsheet->get_schedule();
		if ($schedule) {
			$schedule['success'] = true;
			echo json_encode($schedule);
		}
	}

	public function staff() {
		$staff = $this->Spreadsheet->get_staff();
		if ($staff) {
			$staff['success'] = true;
			echo json_encode($staff);
		}
	}
}
