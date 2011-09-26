<?php

class Staff_v1 extends CI_Model {
	const COURSE_COLUMN = 'course';
	const NAME_COLUMN = 'name';
	const USERNAME_COLUMN = 'username';
	const TABLE = 'staff';

	/**
	 * Get all staff associated with a given course
	 * @param $course [String] Course url
	 *
	 */
	public function get_staff($course) {
		$staff = $this->db->get_where(self::TABLE, array(self::COURSE_COLUMN => $course))->result_array();
		return array('staff' => $staff);
	}

	/**
	 * Get all staff associated with a given course as an associative array (indexed by id)
	 * @param $course [String] Course url
	 *
	 */
	public function get_staff_assoc($course) {
		// get staff from database
		$staff = $this->db->get_where(self::TABLE, array(self::COURSE_COLUMN => $course))->result_array();

		// convert list of staff to associative array, indexed by staff ID
		$return_array = array();
		foreach ($staff as $s)
			$return_array[strval($s['id'])] = $s;

		return $return_array;
	}
}

?>
