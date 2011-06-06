<?php

class Student extends CI_Model {
	// state variables, used by controller as well
	const STATE_HAND_UP = 0;
	const STATE_HELPED = 1;
	const STATE_HAND_DOWN = 2;
	const STATE_CLOSED = 3;

	// column names
	const TABLE = 'students';
	const ID_COLUMN = 'id';
	const NAME_COLUMN = 'name';
	const QUESTION_COLUMN = 'question';
	const CATEGORY_COLUMN = 'category';
	const TIMESTAMP_COLUMN = 'timestamp';
	const TF_COLUMN = 'tf';
	const STATE_COLUMN = 'state';

	// memcache instance
	private $memcache;

	function __construct() {
		parent::__construct();

		// connect to memcache server
		$this->memcache = new Memcache;
		$this->memcache->connect('localhost', 11211) or die("Could not connect to memcache");
	}

	/**
	 * Add a single student
	 * @param $data [Array] Associative array containing student name, question text, and question category
	 *
	 */
	public function add($data) {
		$this->db->insert(self::TABLE, $data);

		// added student means queue has changed, so clear cache
		$this->memcache->flush();
		// flush only guarantees cache is flushed within one second
		sleep(1);
	}

	/**
	 * Dispatch a single student
	 * @param $tf [String] TF student has been dispatched to
	 *
	 */
	public function dispatch($id, $tf) {
		// update student in database
		$this->db->set(array(self::TF_COLUMN => $tf, self::STATE_COLUMN => self::STATE_HELPED))->where(self::ID_COLUMN, $id);
		$this->db->update(self::TABLE);

		// dispatched student means queue has changed, so clear cache
		$this->memcache->flush();
		// flush only guarantees cache is flushed within one second
		sleep(1);
	}

	/**
	 * Get memcache key for the position of a student
	 * Keys for position cache are in the format students:position:<id>
	 * @param $id [Integer] ID of student
	 * @return [String] Memcache key for student
	 *
	 */
	public function get_memcache_position_key($id) {
		return self::TABLE . ':position:' . $id;
	}

	/**
	 * Get the position of a single student
	 * @param $id [Integer] ID of student
	 * @return Array with 'position' and 'changed' boolean flag
	 *
	 */
	public function get_position($id) {
		$key = $this->get_memcache_position_key($id);

		// long-polling: reduce HTTP requests by retrying here (for 25 seconds) rather than from another request
		for ($i = 0; $i < 25; $i++) {
			// query cache for this student's position
			$position = $this->memcache->get($key);

			// cache key expired, so go to database for updated value
			if ($position == null) {
				$queue = $this->get_queue();
				foreach ($queue as $index => $student) {
					if ($student->id == $id) {
						$position = $index;
						break;
					}
				}

				// cache position of student and return new value
				$this->memcache->set($key, $position);
				return array('position' => $position, 'changed' => true);
			}

			// cache key still valid, so sleep and try again
			sleep(1);
		}

		// unchanged after 25 seconds, so return value before server-side timeout
		return array('position' => $position, 'changed' => false);
	}

	/**
	 * Get an ordered queue of all students with their hand up.
	 *
	 */
	public function get_queue() {
		return $this->db->order_by(self::TIMESTAMP_COLUMN, 'asc')->get_where(self::TABLE, 
				array(self::STATE_COLUMN => self::STATE_HAND_UP))->result();
	}

	/**
	 * Set the state of a single student
	 * @param $id [Integer] ID of the student to set
	 * @param $state [Integer] Value of student's state
	 *
	 */
	public function set_state($id, $state) {
		$this->db->set(self::STATE_COLUMN, $state)->where(self::ID_COLUMN, $id);
		$this->db->update(self::TABLE);
	}
}

?>
