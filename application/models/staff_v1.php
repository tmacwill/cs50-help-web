<?php

class Staff_v1 extends CI_Model {
	const COURSE_COLUMN = 'course';
	const ID_COLUMN = 'id';
	const NAME_COLUMN = 'name';
	const USERNAME_COLUMN = 'username';
	const TABLE = 'staff';

	/**
	 * Get all staff associated with a given course
	 * @param $course [String] Course url
	 *
	 */
	public function get_staff($course) {
		// connect to database manually, because questions autoloads staff
		$this->load->database();

		// get all staff, ordered by name
		$staff = $this->db->order_by(self::NAME_COLUMN . ' ASC')->get_where(self::TABLE, 
			array(self::COURSE_COLUMN => $course))->result_array();
		return array('staff' => $staff);
	}

	/**
	 * Get all staff associated with a given course as an associative array (indexed by id)
	 * @param $course [String] Course url
	 *
	 */
	public function get_staff_assoc($course) {
		// connect to database manually, because questions autoloads staff
		$this->load->database();

		// get staff from database
		$staff = $this->get_staff($course);

		// convert list of staff to associative array, indexed by staff ID
		$return_array = array();
		foreach ($staff as $s)
			$return_array[strval($s['id'])] = $s;

		return $return_array;
	}

	/**
	 * Get the username for a given staffer
	 * @param $id [Integer] Staff id
	 * @return Staff username
	 *
	 */
	public function get_info($id) {
		// connect to database manually, because questions autoloads staff
		$this->load->database();

		return $this->db->get_where(self::TABLE, array(self::ID_COLUMN => $id))->row_array();
	}
}

?>
