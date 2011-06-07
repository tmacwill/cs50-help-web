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

	// memcache instance and constants
	private $memcache;
	const MEMCACHE_KEY_QUEUE = 'queue';

	function __construct() {
		parent::__construct();

		// connect to memcache server
		$this->memcache = new Memcache;
		$this->memcache->connect('localhost', 11211) or die("Could not connect to memcache");
	}

	/**
	 * Add a single student
	 * Side effect: clear queue from cache
	 * @param $data [Array] Associative array containing student name, question text, and question category
	 *
	 */
	public function add($data) {
		$this->db->insert(self::TABLE, $data);
		$this->memcache->delete(self::MEMCACHE_KEY_QUEUE);
	}

	/**
	 * Dispatch a single student
	 * Side effect: clear queue from cache
	 * @param $tf [String] TF student has been dispatched to
	 *
	 */
	public function dispatch($id, $tf) {
		// update student in database
		$this->db->set(array(self::TF_COLUMN => $tf, self::STATE_COLUMN => self::STATE_HELPED))->where(self::ID_COLUMN, $id);
		$this->db->update(self::TABLE);

		$this->memcache->delete(self::MEMCACHE_KEY_QUEUE);
	}

	/**
	 * Get an ordered queue of all students with their hand up.
	 * @param $force [Boolean] If true, don't use long polling
	 *
	 */
	public function get_queue($force = false) {
		// check memcache for queue
		$queue = $this->memcache->get(self::MEMCACHE_KEY_QUEUE);

		// long-polling: reduce HTTP requests by retrying here (for 25 seconds) rather than from another request
		for ($i = 0; $i < 3; $i++) {
			// cache key expired or not using long-polling, so go to database for updated value
			if ($queue == null || $force) {
				$queue = $this->db->order_by(self::TIMESTAMP_COLUMN, 'asc')->get_where(self::TABLE, 
							array(self::STATE_COLUMN => self::STATE_HAND_UP))->result();

				// cache result
				$this->memcache->set(self::MEMCACHE_KEY_QUEUE, $queue);
				return array('queue' => $queue, 'changed' => true);
			}

			// cache key still valid, so sleep and try again
			sleep(1);
		}

		return array('queue' => $queue, 'changed' => false);
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
