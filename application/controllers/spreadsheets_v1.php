<?php

class Spreadsheets_v1 extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('Spreadsheet_v1');
	}

	public function categories() {
		$categories = $this->Spreadsheet_v1->get_categories();
		if ($categories) {
			$categories['success'] = true;
			echo json_encode($categories);
		}
	}

	public function schedule() {
		$schedule = $this->Spreadsheet_v1->get_schedule();
		if ($schedule) {
			$schedule['success'] = true;
			echo json_encode($schedule);
		}
	}

	public function staff() {
		$staff = $this->Spreadsheet_v1->get_staff();
		if ($staff) {
			$staff['success'] = true;
			echo json_encode($staff);
		}
	}
}

?>
