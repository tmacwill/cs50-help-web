<?php

class Course_v1 extends CI_Model {
	const URL_COLUMN = 'url';
	const TABLE = 'courses';

	public function get_categories_url($url) {
		$row = $this->db->get_where(self::TABLE, array(self::URL_COLUMN => $url))->row_array();
		return $row['categories_url'];
	}

	public function get_schedule_url($url) {
		$row = $this->db->get_where(self::TABLE, array(self::URL_COLUMN => $url))->row();
		return $row['schedule_url'];
	}

	public function get_staff_url($url) {
		$row = $this->db->get_where(self::TABLE, array(self::URL_COLUMN => $url))->row();
		return $row['staff_url'];
	}
}

?>
