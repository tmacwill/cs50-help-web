<?php

class Question extends CI_Model {
	// state variables, used by controller as well
	const STATE_HAND_UP = 0;
	const STATE_DISPATCHED = 1;
	const STATE_HAND_DOWN = 2;
	const STATE_CLOSED = 3;
	const STATE_COMPLETED = 4;

	// column names
	const TABLE = 'questions';
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
	const MEMCACHE_KEY_DISPATCHED = 'dispatched';
	const MEMCACHE_SUFFIX_UPDATE = '_last_update';
	const LONG_POLLING_TIMEOUT = 25;

	function __construct() {
		parent::__construct();
		session_start();

		// make sure session keys exist
		if (!isset($_SESSION[self::MEMCACHE_KEY_QUEUE . self::MEMCACHE_SUFFIX_UPDATE]))
			$_SESSION[self::MEMCACHE_KEY_QUEUE . self::MEMCACHE_SUFFIX_UPDATE] = '';

		if (!isset($_SESSION[self::MEMCACHE_KEY_DISPATCHED . self::MEMCACHE_SUFFIX_UPDATE]))
			$_SESSION[self::MEMCACHE_KEY_DISPATCHED . self::MEMCACHE_SUFFIX_UPDATE] = '';

		// end session so other requests can be processed
		session_write_close();

		// connect to memcache server
		$this->memcache = new Memcache;
		$this->memcache->connect('localhost', 11211) or die("Could not connect to memcache");
	}

	/**
	 * Add a single question
	 * Side effect: clear queue from cache
	 * @param $data [Array] Associative array containing student name, question text, and question category
	 *
	 */
	public function add($data) {
		// check if student already has his/her hand up
		$student = $this->db->get_where(self::TABLE, array(self::NAME_COLUMN => $data[self::NAME_COLUMN], 
					self::STATE_COLUMN => self::STATE_HAND_UP))->row();

		// student is not present yet, so add new question
		if (!$student) {
			$this->db->insert(self::TABLE, $data);
			$this->memcache->delete(self::MEMCACHE_KEY_QUEUE);
			$this->memcache->set(self::MEMCACHE_KEY_QUEUE . self::MEMCACHE_SUFFIX_UPDATE, (string)time());
		}
	}

	/**
	 * Dispatch a single question
	 * Side effect: clear dispatch history from cache
	 * @param $tf [String] TF student has been dispatched to
	 *
	 */
	public function dispatch($id, $tf) {
		// get the student associated with this question 
		$student = $this->db->get_where(self::TABLE, array(self::ID_COLUMN => $id))->row();
		// mark all student's previous questions as completed
		$this->db->set(array(self::STATE_COLUMN => self::STATE_COMPLETED))->
			where(array(self::NAME_COLUMN => $student->{self::NAME_COLUMN}, self::ID_COLUMN . ' <>' => $id));
		$this->db->update(self::TABLE);

		// mark student's current question as dispatched
		$this->db->set(array(self::TF_COLUMN => $tf, self::STATE_COLUMN => self::STATE_DISPATCHED))->where(self::ID_COLUMN, $id);
		$this->db->update(self::TABLE);

		// expire both queue and dispatch cache
		$this->memcache->delete(self::MEMCACHE_KEY_QUEUE);
		$this->memcache->delete(self::MEMCACHE_KEY_DISPATCHED);
		$this->memcache->set(self::MEMCACHE_KEY_QUEUE . self::MEMCACHE_SUFFIX_UPDATE, (string)time());
		$this->memcache->set(self::MEMCACHE_KEY_DISPATCHED . self::MEMCACHE_SUFFIX_UPDATE, (string)time());
	}

	/**
	 * Get list of students' most recent dispatches
	 * @param $force [Boolean] If true, force an immediate read from DB
	 *
	 */
	public function get_dispatched($force = false) {
		return $this->long_poll(self::MEMCACHE_KEY_DISPATCHED, self::STATE_DISPATCHED, $force);
	}

	/**
	 * Get an ordered queue of all students with their hand up.
	 * @param $force [Boolean] If true, force an immediate read from DB
	 *
	 */
	public function get_queue($force = false) {
		return $this->long_poll(self::MEMCACHE_KEY_QUEUE, self::STATE_HAND_UP, $force);
	}

	/**
	 * Use long polling to get all questions with the given state
	 * @param $memcache_key [String] Key used to store list in cache
	 * @param $state [Integer] State of question in database
	 * @param $force [Boolean] If true, force an immediate read from DB
	 *
	 */
	private function long_poll($memcache_key, $state, $force = false) {
		$update_key = $memcache_key . self::MEMCACHE_SUFFIX_UPDATE;

		// get last update from session and end immediately so other requests can process
		session_start();
		$last_update = $_SESSION[$update_key];
		session_write_close();

		// long-polling: reduce HTTP requests by retrying here (for 25 seconds) rather than from another request
		for ($i = 0; $i < self::LONG_POLLING_TIMEOUT; $i++) {
			// query cache before going to database
			$result = $this->memcache->get($memcache_key);

			// cache key expired or not using long-polling, so go to database for updated value
			if ($result === null || $result === false || $force) {
				// get all students with matching state for current date
				$result = $this->db->order_by(self::TIMESTAMP_COLUMN, 'asc')->
					get_where(self::TABLE, array(self::STATE_COLUMN => $state, 
								'DATE(' . self::TIMESTAMP_COLUMN . ')' => date('Y-m-d')))->result();

				// empty queue and null key are two very different things
				if ($result === null)
					$result = '';

				// cache result
				$this->memcache->set($memcache_key, $result);
				$this->memcache->set($update_key, (string)time());
				return array($memcache_key => $result, 'changed' => true, 'source' => 'db');
			}

			// cache has been updated since our last read
			else if ($last_update != $this->memcache->get($update_key)) {
				// restart session so we can store new key
				session_start();
				$_SESSION[$update_key] = $this->memcache->get($update_key);
				session_write_close();

				return array($memcache_key => $result, 'changed' => true, 'source' => 'memcache');
			}

			// cache key still valid, so sleep and try again
			sleep(1);
		}

		return array($memcache_key => $result, 'changed' => false);
	}

	/**
	 * Set the state of a single question
	 * @param $id [Integer] ID of the student to set
	 * @param $state [Integer] Value of student's state
	 *
	 */
	public function set_state($id, $state) {
		$this->db->set(self::STATE_COLUMN, $state)->where(self::ID_COLUMN, $id);
		$this->db->update(self::TABLE);
		$this->memcache->delete(self::MEMCACHE_KEY_QUEUE);
	}
}

?>
