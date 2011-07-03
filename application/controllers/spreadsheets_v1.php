<?php

class Spreadsheets_v1 extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('Spreadsheet_v1');
	}

	public function categories($course) {
		$categories = $this->Spreadsheet_v1->get_categories($course);
		if ($categories) {
			$categories['success'] = true;
			echo json_encode($categories);
		}
	}

	public function schedule($course) {
		$schedule = $this->Spreadsheet_v1->get_schedule($course);
		if ($schedule) {
			$schedule['success'] = true;
			echo json_encode($schedule);
		}
	}

	public function staff($course) {
		$staff = $this->Spreadsheet_v1->get_staff($course);
		if ($staff) {
			$staff['success'] = true;
			echo json_encode($staff);
		}
	}
}

?>
