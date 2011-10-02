<?php

class Schedule_v1 extends CI_Model {
	const COURSE_COLUMN = 'course';
	const DAY_COLUMN = 'day';
	const STAFF_ID_COLUMN = 'staff_id';

	const TABLE = 'schedule';
	const STAFF_TABLE = 'staff';

	function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Get the IDs of all staff members on duty today
	 * @param $course [String] Course ID for staff
	 *
	 */
	public function get_on_duty_ids($course) {
		// get text of today's day
		$day = date('l');

		// get IDs of staff members who belong to the given course and are on duty today
		$result = $this->db->select(self::STAFF_ID_COLUMN)->join(self::STAFF_TABLE, self::TABLE . '.staff_id = ' . self::STAFF_TABLE . '.id')->
			get_where(self::TABLE, array(self::TABLE . '.' . self::DAY_COLUMN => $day, self::STAFF_TABLE . '.course' => $course))->
			result_array();

		// convert code igniter array to single-dimensional array of IDs
		$return_array = array();
		foreach ($result as $row)
			$return_array[] = $row[self::STAFF_ID_COLUMN];

		return $return_array;
	}
}

?>
