<?php

class Question_v1 extends CI_Model {
	// state variables, used by controller as well
	const STATE_HAND_UP = 0;
	const STATE_DISPATCHED = 1;
	const STATE_HAND_DOWN = 2;
	const STATE_CLOSED = 3;
	const STATE_COMPLETED = 4;

	// column names
	const TABLE = 'questions';
	const ID_COLUMN = 'id';
	const STUDENT_ID_COLUMN = 'student_id';
	const NAME_COLUMN = 'name';
	const COURSE_COLUMN = 'course';
	const QUESTION_COLUMN = 'question';
	const SHOW_COLUMN = 'show';
	const CATEGORY_COLUMN = 'category';
	const CATEGORY_COLOR_COLUMN = 'category_color';
	const TIMESTAMP_COLUMN = 'timestamp';
	const DISPATCH_TIMESTAMP_COLUMN = 'dispatch_timestamp';
	const TF_COLUMN = 'tf';
	const STATE_COLUMN = 'state';

	// memcache instance and constants
	private $memcache;
	const MEMCACHE_KEY_CAN_ASK = '_can_ask';
	const MEMCACHE_KEY_DISPATCHED = '_dispatched';
	const MEMCACHE_KEY_QUEUE = '_queue';
	const MEMCACHE_SUFFIX_UPDATE = '_last_update';
	const LONG_POLLING_TIMEOUT = 25;

	function __construct() {
		parent::__construct();
		$this->load->model('Course_v1');

		// connect to memcache server
		$this->memcache = new Memcache;
		$this->memcache->connect('localhost', 11211) or die("Could not connect to memcache");
	}

	/**
	 * Add a single question
	 * @param $data [Array] Associative array containing student name, question text, and question category
	 *
	 */
	public function add($data, $course) {
		if (!isset($data[self::STUDENT_ID_COLUMN]) || !isset($data[self::QUESTION_COLUMN]) || !isset($data[self::CATEGORY_COLUMN]))
			return false;

		// check if student already has his/her hand up
		$student = $this->db->get_where(self::TABLE, array(self::COURSE_COLUMN => $course, 
					self::STUDENT_ID_COLUMN => $data[self::STUDENT_ID_COLUMN], self::STATE_COLUMN => self::STATE_HAND_UP))->row();

		// student is not present yet and OHs are still in session, so add new question
		if (!$student && $this->can_ask($course)) {
			$data[self::COURSE_COLUMN] = $course;
			$this->db->insert(self::TABLE, $data);
			$this->memcache->delete($this->get_key_queue($course));
			$this->memcache->set($this->get_key_queue_update($course), (string)time());
			return true;
		}
		else
			return false;
	}

	/**
	 * Check whether or not OHs are in session, and therefore accepting new questions
	 * @param $course [String] Course url
	 *
	 */
	public function can_ask($course) {
		return $this->memcache->get($this->get_key_can_ask($course));
	}

	/**
	 * Dispatch a list of questions to a TF
	 * Side effect: clear dispatch history and queue from cache
	 * @param $ids [Array] Array of question IDs to dispatch
	 * @param $tf [String] TF student has been dispatched to
	 * @param $course [String] Course url
	 *
	 */
	public function dispatch($ids, $tf, $course) {
		if (empty($ids) || empty($tf)) 
			return false;

		// get student ids corresponding to question ids
		$student_id_results = $this->db->select(self::STUDENT_ID_COLUMN)->where_in(self::ID_COLUMN, $ids)->
			get(self::TABLE)->result_array();

		// build array of student ids
		$student_ids = array();
		foreach ($student_id_results as $student_id)
			$student_ids[] = $student_id[self::STUDENT_ID_COLUMN];

		// mark all students' previous questions as completed
		if (!empty($student_ids)) {
			$this->db->set(array(self::STATE_COLUMN => self::STATE_COMPLETED))->where_in(self::STUDENT_ID_COLUMN, $student_ids)->
				where(array(self::STATE_COLUMN => self::STATE_HAND_UP, self::COURSE_COLUMN => $course));
			$this->db->update(self::TABLE);
		}

		// mark questions as dispatched
		$this->db->set(array(self::TF_COLUMN => $tf, self::STATE_COLUMN => self::STATE_DISPATCHED))->set(self::DISPATCH_TIMESTAMP_COLUMN, 'NOW()', false)->where_in(self::ID_COLUMN, $ids);
		$this->db->update(self::TABLE);

		// update both queue and dispatch cache
		$this->memcache->delete($this->get_key_queue($course));
		$this->memcache->delete($this->get_key_dispatched($course));
		$this->memcache->set($this->get_key_queue_update($course), (string)time());
		$this->memcache->set($this->get_key_dispatched_update($course), (string)time());

		return true;
	}

	/**
	 * Get list of students' most recent dispatches
	 * @param $force [Boolean] If true, force an immediate read from DB
	 *
	 */
	public function get_dispatched($course, $force = false) {
		return $this->long_poll($this->get_key_dispatched($course), self::STATE_DISPATCHED, $course, $force);
	}

	/**
	 * Get cache key for whether or not OHs are in session
	 * @param [String] $course Course url
	 * @return Cache key
	 *
	 */
	private function get_key_can_ask($course) {
		return $course . self::MEMCACHE_KEY_CAN_ASK;
	}

	/**
	 * Get cache key for the list of dispatched questions
	 * @param [String] $course Course url
	 * @return Cache key
	 *
	 */
	private function get_key_dispatched($course) {
		return $course . self::MEMCACHE_KEY_DISPATCHED;
	}

	/**
	 * Get cache key for a course's last cache update
	 * @param [String] $course Course url
	 * @return Cache key
	 *
	 */
	private function get_key_dispatched_update($course) {
		return $course . self::MEMCACHE_KEY_DISPATCHED . self::MEMCACHE_SUFFIX_UPDATE;
	}

	/**
	 * Get cache key for questions queue
	 * @param [String] $course Course url
	 * @return Cache key
	 *
	 */
	private function get_key_queue($course) {
		return $course . self::MEMCACHE_KEY_QUEUE;
	}

	/**
	 * Get cache key for a course's last cache update
	 * @param [String] $course Course url
	 * @return Cache key
	 *
	 */
	private function get_key_queue_update($course) {
		return $course . self::MEMCACHE_KEY_QUEUE . self::MEMCACHE_SUFFIX_UPDATE;
	}

	/**
	 * Get an ordered queue of all students with their hand up.
	 * @param $force [Boolean] If true, force an immediate read from DB
	 *
	 */
	public function get_queue($course, $force = false) {
		// get all active questions for the current course
		$queue = $this->long_poll($this->get_key_queue($course), self::STATE_HAND_UP, $course, $force);

		// assign positions and hide hidden questions
		$i = 0;
		foreach ($queue[$this->get_key_queue($course)] as $q) {
			// TODO: convert long_polll to use result_array to avoid hard-coding column names here
			$q->position = ++$i;
			if (!$q->show) {
				$q->name = 'Invisible';
				$q->question = '';
				$q->category = '';
			}
		}
		
		return $queue;
	}

	/** 
	 * User has logged in, so re-activate any closed window questions
	 * @param $student_id [String] Unique ID of student
	 * @param $course [String] Course url
	 *
	 */
	public function login($student_id, $course) {
		$this->db->set(self::STATE_COLUMN, self::STATE_HAND_UP)->
			where(array(self::STUDENT_ID_COLUMN => $student_id, self::STATE_COLUMN => self::STATE_CLOSED));
		$this->db->update(self::TABLE);
	}

	/**
	 * Use long polling to get all questions with the given state
	 * @param $memcache_key [String] Key used to store list in cache
	 * @param $state [Integer] State of question in database
	 * @param $course [String] Course url
	 * @param $force [Boolean] If true, force an immediate read from DB
	 *
	 */
	private function long_poll($memcache_key, $state, $course, $force = false) {
		$update_key = $memcache_key . self::MEMCACHE_SUFFIX_UPDATE;

		// make sure session key exists
		session_start();
		if (!isset($_SESSION[$update_key]))
			$_SESSION[$update_key] = '';

		// get last update from session and end immediately so other requests can process
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
					get_where(self::TABLE, array(self::STATE_COLUMN => $state, self::COURSE_COLUMN => $course))->result();

				// empty queue and null key are two very different things
				if ($result === null)
					$result = '';

				// cache result
				$update = (string)time();
				$_SESSION[$update_key] = $update;
				$this->memcache->set($memcache_key, $result);
				$this->memcache->set($update_key, $update);
				return array($memcache_key => $result, 'changed' => true, 'source' => 'db');
			}

			// cache has been updated since our last read
			else if ($last_update != $this->memcache->get($update_key)) {
				// restart session so we can store new key
				session_start();
				$_SESSION[$update_key] = $this->memcache->get($update_key);
				session_write_close();

				// return updated data from the cache
				return array($memcache_key => $result, 'changed' => true, 'source' => 'memcache');
			}

			// cache data still valid, so sleep and try again
			sleep(1);
		}

		return array($memcache_key => $result, 'changed' => false);
	}

	/**
	 * Set the visibility of a single question
	 * @param $id [Integer] ID of the question to set
	 * @param $state [Integer] Value of question's visibility
	 *
	 */
	public function set_show($id, $show, $course) {
		if (empty($id))
			return false;

		$this->db->set(self::SHOW_COLUMN, $show)->where(array(self::ID_COLUMN => $id));
		$this->db->update(self::TABLE);
		$this->memcache->delete($this->get_key_queue($course));

		return true;
	}

	/**
	 * Set the state of a single question
	 * @param $id [Integer] ID of the question to set
	 * @param $state [Integer] Value of question's state
	 *
	 */
	public function set_state($id, $state, $course) {
		if (empty($id) || empty($state))
			return false;

		// update database and invalidate cache
		$this->db->set(self::STATE_COLUMN, $state)->where(array(self::ID_COLUMN => $id));
		$this->db->update(self::TABLE);
		$this->memcache->delete($this->get_key_queue($course));

		return true;
	}
}

?>
