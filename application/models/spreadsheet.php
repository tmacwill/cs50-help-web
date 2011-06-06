<?php

class Spreadsheet extends CI_Model {
	public static $CATEGORIES_URL = 'https://spreadsheets.google.com/spreadsheet/pub?hl=en_US&hl=en_US&key=0Ah3bwLWjJUiPdFR0S09PaFVPWS12ZHJXdDNzSFJxUlE&output=csv';

	public function __construct() {
		parent::__construct();
	}

	public function get_categories() {
		// get current date as month/day/year, matching GDoc's format
		$date = date('n/j/Y');

		// get CSV of published spreadsheet
		$curl = curl_init(self::$CATEGORIES_URL);
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
			array_push($return_array, $row[$column]);
		}

		// remote date from list of categories
		array_shift($return_array);
		return $return_array;
	}
}

?>
