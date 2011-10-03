<?php

class Categories_v1 extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('Category_v1');
	}

	public function today($course) {
		$categories = $this->Category_v1->get_categories($course);
		if ($categories) {
			$categories['success'] = true;
			echo json_encode($categories);
		}
	}
}

?>
