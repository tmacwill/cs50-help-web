<?php

class Category_v1 extends CI_Model {
	const CATEGORY_COLUMN = 'catgory';
	const COURSE_COLUMN = 'course';
	const DATE_COLUMN = 'date';
	const TABLE = 'categories';

	function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Get all categories for today
	 * @param $course [String] Course url
	 *
	 */
	public function get_categories($course) {
		$start = date('Y-m-d H:i:s', strtotime('today 00:00:00'));
		$end = date('Y-m-d H:i:s', strtotime('today 23:59:59'));
		$categories = $this->db->get_where(self::TABLE, array(self::COURSE_COLUMN => $course, 
			self::DATE_COLUMN . ' >' => $start, self::DATE_COLUMN . ' <' => $end))->result_array();

		return array('categories' => $categories);
	}

}

?>
