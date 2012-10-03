<?php 
/*
 feedback
 --------------------------
  `id`            int unsigned  AUTO_INCREMENT NOT NULL,
  `pid`           int unsigned                 NOT NULL,
  `created_by`    tinytext                     NOT NULL,
  `created_on`    datetime                     NOT NULL,
  `message`       text                         NOT NULL,
  PRIMARY KEY (`id`)
----------------------------
*/
class Feedback_model extends CI_Model {
	public function __construct() {
		parent::__construct();
	}

	public function feedback_id ($id) {
		$q = $this->db->select("id, created_by, created_on, message")
			->from("feedback")
			->where("id", $id);

		return $q->get()->result();
	}

	/* Return feedback per project id */
	
	public function feedback($pid) {
		$q = $this->db->select("id, created_by, created_on, message")
			->from("feedback")
			->where("pid", $pid);

		return $q->get()->result();
	}
}