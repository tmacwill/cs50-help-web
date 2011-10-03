<?php

class Course_v1 extends CI_Model {
	const NAME_COLUMN = 'name';
	const URL_COLUMN = 'url';
	const TABLE = 'courses';

	/**
	 * DEPRECATED IN BY v1.1
	 * Get url of categories spreadsheet
	 * @param $url Course url
	 *
	 */
	public function get_categories_url($url) {
		$row = $this->db->get_where(self::TABLE, array(self::URL_COLUMN => $url))->row_array();
		return $row['categories_url'];
	}

	/**
	 * Get all registered courses
	 *
	 */
	public function get_courses() {
		$this->load->database();
		$courses = $this->db->select(self::NAME_COLUMN . ',' . self::URL_COLUMN)->get(self::TABLE)->result();
		return array('courses' => $courses);
	}

	/**
	 * DEPRECATED IN BY v1.1
	 * Get url or schedule gcal
	 * @param $url Course url
	 *
	 */
	public function get_schedule_url($url) {
		$row = $this->db->get_where(self::TABLE, array(self::URL_COLUMN => $url))->row_array();
		return $row['schedule_url'];
	}

	/**
	 * DEPRECATED IN BY v1.1
	 * Get url of staff spreadsheet
	 * @param $url Course url
	 *
	 */
	public function get_staff_url($url) {
		$row = $this->db->get_where(self::TABLE, array(self::URL_COLUMN => $url))->row_array();
		return $row['staff_url'];
	}

	/**
	 * DEPRECATED IN BY v1.1
	 * Get url of students spreadsheet
	 * @param $url Course url
	 *
	 */
	public function get_students_url($url) {
		$row = $this->db->get_where(self::TABLE, array(self::URL_COLUMN => $url))->row_array();
		return $row['students_url'];
	}
}

?>
