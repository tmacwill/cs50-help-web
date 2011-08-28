<?php

// connect to memcache
$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die("Could not connect to memcache");

// get all course schedules
mysql_connect('localhost', 'cs50help', 'cs50help');
mysql_select_db('cs50help');
$query = 'SELECT schedule_url, url FROM courses';
$result = mysql_query($query);

// iterate over courses
while ($row = mysql_fetch_assoc($result)) {
	// fetch XML of schedule data
	$course = $row['url'];
	$curl = curl_init($row['schedule_url']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$schedule_xml = new SimpleXMLElement(curl_exec($curl));
	curl_close($curl);

	// iterate over entries
	$can_ask = false;
	foreach ($schedule_xml->entry as $event) {
		// extract date from gcal string
		preg_match('/When: (\w+) (\w+) (\d+), (\d+) ([0-9apm:]+) to ([0-9apm:]+)/', (string)$event->summary, $matches);
		$start_string = "{$matches[1]} {$matches[2]} {$matches[3]}, {$matches[4]} {$matches[5]}";
		$end_string = "{$matches[1]} {$matches[2]} {$matches[3]}, {$matches[4]} {$matches[6]}";
		$start = date('D M d, Y h:ia', strtotime($start_string));
		$end = date('D M d, Y h:ia', strtotime($end_string));
		$current = date('D M d, Y h:ia');

		// if current timestamp falls in an OH slot, then students can ask questions
		if ($start <= $current && $end >= $current) {
			$can_ask = true;
			break;
		}
	}

	// set memcache flag
	$memcache_key = $course . '_can_ask';
	$memcache->set($memcache_key, $can_ask, 0, 0);
}

?>
