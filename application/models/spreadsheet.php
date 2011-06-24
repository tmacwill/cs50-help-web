<?php

class Spreadsheet extends CI_Model {
	const CATEGORIES_URL = 'https://spreadsheets.google.com/spreadsheet/pub?hl=en_US&hl=en_US&key=0Ah3bwLWjJUiPdDg1OEhGNmZORTlNcHVRUi1XdXRjWXc&single=true&gid=1&output=csv';
	const SCHEDULE_URL = 'https://www.google.com/calendar/feeds/djsch5ddcameaq4637tjio45r4%40group.calendar.google.com/public/basic';
	const STAFF_URL = 'https://spreadsheets.google.com/spreadsheet/pub?hl=en_US&hl=en_US&key=0Ah3bwLWjJUiPdDg1OEhGNmZORTlNcHVRUi1XdXRjWXc&single=true&gid=0&output=csv';

	public function __construct() {
		parent::__construct();
	}

	public function get_categories() {
		// get current date as month/day/year, matching GDoc's format
		$date = date('n/j/Y');

		// get CSV of published spreadsheet
		$curl = curl_init(self::CATEGORIES_URL);
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
			$return_array[] = $row[$column];
		}

		// remote date from list of categories
		array_shift($return_array);
		return array('categories' => $return_array);
	}

	/**
	 * Get the staff on duty today based on a GCal
	 *
	 */
	public function get_schedule() {
		// get all TFs/CAs
		$staff = $this->get_staff();

		// get XML representation of GCal
		$curl = curl_init(self::SCHEDULE_URL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$schedule_xml = new SimpleXMLElement(curl_exec($curl));
		curl_close($curl);

		// search for today's date
		foreach ($schedule_xml->entry as $event) {
			$matches = array();
			preg_match('/When: (\w+ \w+ \d+, \d+)/', (string)$event->summary, $matches);
			$date = $matches[1];
			if (date('D M d, Y') == $matches[1]) {
				// title of event must be CSV of staff
				$on_duty = explode(',', (string)$event->title);
				$on_duty = array_map('trim', $on_duty);
				break;
			}
		}

		// determine if each staff member is on duty today
		$return_array = array();
		foreach ($staff['staff'] as $tf) {
			if (isset($on_duty))
				$return_array[] = array('name' => trim($tf), 'on_duty' => in_array(trim($tf), $on_duty));
			else
				$return_array[] = array('name' => trim($tf), 'on_duty' => false);
		}

		return array('schedule' => $return_array);
	}

	public function get_staff() {
		// get CSV of published spreadsheet
		$curl = curl_init(self::STAFF_URL);
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

		return array('staff' => $return_array);
	}
}

?>
