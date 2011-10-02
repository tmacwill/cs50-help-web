<?php

class Attendance_v1 extends CI_Model {
	const ID_COLUMN = 'id';
	const STAFF_ID_COLUMN = 'staff_id';
	const TIMESTAMP_COLUMN = 'timestamp';
	const TABLE = 'attendance';

	function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Log the arrival of a staff member
	 * @param $staff_id [Integer] Staff ID
	 *
	 */
	public function arrival($staff_id) {
		$this->db->insert(self::TABLE, array(self::STAFF_ID_COLUMN => $staff_id));
		return true;
	}
	
}

?>
