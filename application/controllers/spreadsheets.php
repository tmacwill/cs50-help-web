<?php

class Spreadsheets extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('Spreadsheet');
	}

	public function categories() {
		echo json_encode($this->Spreadsheet->get_categories());
	}
}
