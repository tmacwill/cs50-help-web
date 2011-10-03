<?php

class Spreadsheet_v1 extends CI_Model {
	const USERNAME_COLUMN = 'username';
	const NAME_COLUMN = 'name';
	const PHONE_COLUMN = 'phone';
	const EMAIL_COLUMN = 'email';

	public function __construct() {
		parent::__construct();
		$this->load->model('Course_v1');
	}

	public function get_categories($course) {
		// get current date as month/day/year, matching GDoc's format
		$date = date('n/j/Y');

		// get CSV of published spreadsheet
		$url = $this->Course_v1->get_categories_url($course);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$spreadsheet = explode("\n", curl_exec($curl));
		curl_close($curl);

		// find today's column
		$column = 0;
		$dates_row = explode(",", $spreadsheet[0]);
		foreach ($dates_row as $key => $value) {
			if ($date == $value) {
				$column = $key;
				break;
			}
		}

		// get all categories in today's column
		$return_array = array();
		foreach ($spreadsheet as $row_csv) {
			$row = explode(",", $row_csv);
			if (isset($row[$column]))
				$return_array[] = array('category' => $row[$column]);
		}

		// remote date from list of categories
		array_shift($return_array);
		return array('categories' => $return_array);
	}

	/**
	 * Get the staff on duty today based on a GCal
	 *
	 */
	public function get_schedule($course) {
		date_default_timezone_set("America/New_York");
		// get all TFs/CAs
		$staff = $this->get_staff($course);


		$vcalendar = new vcalendar();


		// get XML representation of GCal
		$url = $this->Course_v1->get_schedule_url($course);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$schedule_xml = new SimpleXMLElement(curl_exec($curl));
		curl_close($curl);

		// search for today's date
		foreach ($schedule_xml->entry as $event) {
			$matches = array();
			preg_match('/When: (\w+ \w+ \d+, \d+)/', (string)$event->summary, $matches);
			if (count($matches) > 0) {
				$date = $matches[1];
				if (date('D M j, Y') == $matches[1]) {
					// title of event must be CSV of staff
					$on_duty = explode(',', (string)$event->title);
					$on_duty = array_map('trim', $on_duty);
					break;
				}
			}
		}

		// determine if each staff member is on duty today
		foreach ($staff['staff'] as $key => $value) {
			if (isset($on_duty))
				$staff['staff'][$key]['on_duty'] = in_array($value['username'], $on_duty);
			else
				$staff['staff'][$key]['on_duty'] = false;
		}

		return array('schedule' => $staff['staff']);
	}

	public function get_staff($course) {
		// get CSV of published spreadsheet
		$url = $this->Course_v1->get_staff_url($course);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$spreadsheet = explode("\n", curl_exec($curl));
		curl_close($curl);

		// determine column indices
		$username_col = 0;
		$name_col = 0;
		$email_col = 0;
		$phone_col = 0;
		foreach (explode(',', $spreadsheet[0]) as $key => $value) {
			if (strtolower(trim($value)) == self::USERNAME_COLUMN)
				$username_col = $key;
			else if (strtolower(trim($value)) == self::NAME_COLUMN)
				$name_col = $key;
			else if (strtolower(trim($value)) == self::EMAIL_COLUMN)
				$email_col = $key;
			else if (strtolower(trim($value)) == self::PHONE_COLUMN)
				$phone_col = $key;
		}

		// iterate over rows, skipping first row
		$return_array = array();
		array_shift($spreadsheet);
		foreach ($spreadsheet as $row_csv) {
			$row = explode(",", $row_csv);
			$return_array[] = array(
				self::USERNAME_COLUMN => trim($row[$username_col]),
				self::NAME_COLUMN => trim($row[$name_col]),
				self::EMAIL_COLUMN => trim($row[$email_col]),
				self::PHONE_COLUMN => trim($row[$phone_col]),
			);
		}

		return array('staff' => $return_array);
	}

	public function get_students($course) {
		// get CSV of published spreadsheet
		$url = $this->Course_v1->get_students_url($course);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$spreadsheet = explode("\n", curl_exec($curl));
		curl_close($curl);

		// iterate over rows, skipping first row
		$return_array = array();
		array_shift($spreadsheet);
		foreach ($spreadsheet as $row_csv) {
			$row = explode(",", $row_csv);
			$return_array[] = trim($row[0]);
		}

		return array('students' => $return_array);
	}
}

?>
