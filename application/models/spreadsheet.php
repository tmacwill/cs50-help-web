<?php

class Spreadsheet extends CI_Model {
	const CATEGORIES_URL = 'https://spreadsheets.google.com/spreadsheet/pub?hl=en_US&hl=en_US&key=0Ah3bwLWjJUiPdFR0S09PaFVPWS12ZHJXdDNzSFJxUlE&output=csv';
	const SCHEDULE_URL = 'https://spreadsheets.google.com/spreadsheet/pub?hl=en_US&hl=en_US&key=0Ah3bwLWjJUiPdHB3eGtuVDlLYlBjaFBUX25yT0Mtb3c&output=csv';

	public function __construct() {
		parent::__construct();
	}

	public function get_categories() {
		return $this->get_column_by_date(self::CATEGORIES_URL);
	}

	public function get_schedule() {
		return $this->get_column_by_date(self::SCHEDULE_URL);
	}

	private function get_column_by_date($url) {
		// get current date as month/day/year, matching GDoc's format
		$date = date('n/j/Y');

		// get CSV of published spreadsheet
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
			$return_array[] = $row[$column];
		}

		// remote date from list of categories
		array_shift($return_array);
		if ($url == self::CATEGORIES_URL)
			return array('categories' => $return_array);
		else if ($url == self::SCHEDULE_URL)
			return array('schedule' => $return_array);
	}
}

?>
