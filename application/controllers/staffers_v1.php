<?php

class Staffers_v1 extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('Attendance_v1');
		$this->load->model('Schedule_v1');
		$this->load->model('Staff_v1');
	}

	/**
	 * Log the arrival of a staff member
	 * @param $id [Integer] Staff id
	 *
	 */
	public function arrival($course) {
		if ($this->Attendance_v1->arrival($this->input->post('id')))
			echo json_encode(array('success' => true));
		else
			echo json_encode(array('success' => false));
	}


	/**
	 * Get all staffers, indicating who is on duty
	 * @param $course [String] Course to get staff for
	 *
	 */
	public function schedule($course) {
		// get list of on duty staffers and all staffers
		$on_duty_ids = $this->Schedule_v1->get_on_duty_ids($course);
		$staff_array = $this->Staff_v1->get_staff($course);

		// make sure we have a list to work with
		if (!$staff_array) {
			echo json_encode(array('success' => false));
			return;
		}

		// get list of staff members from response
		$staff = $staff_array['staff'];
		$length = count($staff);

		// iterate over staff members
		for ($i = 0; $i < $length; $i++) {
			// mark on duty staffers as on duty
			if (in_array($staff[$i]['id'], $on_duty_ids)) 
				$staff[$i]['on_duty'] = true;
			else
				$staff[$i]['on_duty'] = false;
		}

		// convert associative array to indexed array
		$staff_array['schedule'] = array_values($staff);
		unset($staff_array['staff']);
		$staff_array['success'] = true;
		echo json_encode($staff_array);
	}

	/**
	 * Get a list of all staffers
	 * @param $course [String] Course to get staff for
	 *
	 */
	public function staff($course) {
		$staff = $this->Staff_v1->get_staff($course);
		if ($staff) {
			$staff['success'] = true;
			echo json_encode($staff);
		}
	}
}

?>
