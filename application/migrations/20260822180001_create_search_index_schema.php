<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Search index queue + state for external search providers.
 */
class Migration_Create_search_index_schema extends MY_Migration {

	public function up()
	{
		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->up_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		}
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function up_mysql()
	{
		if (!$this->db->table_exists('search_index_queue')) {
			$this->db->query("
				CREATE TABLE `search_index_queue` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`object_type` VARCHAR(32) NOT NULL,
					`object_id` INT(11) NOT NULL,
					`object_key` VARCHAR(200) NOT NULL,
					`change_class` VARCHAR(32) NOT NULL,
					`status` VARCHAR(16) NOT NULL DEFAULT 'pending',
					`attempts` INT(11) NOT NULL DEFAULT 0,
					`last_error` VARCHAR(500) DEFAULT NULL,
					`changed` INT(11) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `uk_search_index_queue_object` (`object_type`, `object_id`),
					KEY `idx_search_index_queue_status_changed` (`status`, `changed`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
			");
		}

		if (!$this->db->table_exists('search_index_state')) {
			$this->db->query("
				CREATE TABLE `search_index_state` (
					`object_type` VARCHAR(32) NOT NULL,
					`object_id` INT(11) NOT NULL,
					`object_key` VARCHAR(200) NOT NULL,
					`status` VARCHAR(16) NOT NULL,
					`changed` INT(11) NOT NULL,
					PRIMARY KEY (`object_type`, `object_id`),
					KEY `idx_search_index_state_status` (`status`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
			");
		}
	}

	private function up_sqlsrv()
	{
		if (!$this->db->table_exists('search_index_queue')) {
			$this->db->query("
				CREATE TABLE search_index_queue (
					id INT NOT NULL IDENTITY(1,1),
					object_type VARCHAR(32) NOT NULL,
					object_id INT NOT NULL,
					object_key VARCHAR(200) NOT NULL,
					change_class VARCHAR(32) NOT NULL,
					status VARCHAR(16) NOT NULL CONSTRAINT df_search_index_queue_status DEFAULT 'pending',
					attempts INT NOT NULL CONSTRAINT df_search_index_queue_attempts DEFAULT 0,
					last_error VARCHAR(500) NULL,
					changed INT NOT NULL,
					PRIMARY KEY (id),
					CONSTRAINT uk_search_index_queue_object UNIQUE (object_type, object_id)
				)
			");
			$this->db->query("CREATE NONCLUSTERED INDEX idx_search_index_queue_status_changed ON search_index_queue (status, changed)");
		}

		if (!$this->db->table_exists('search_index_state')) {
			$this->db->query("
				CREATE TABLE search_index_state (
					object_type VARCHAR(32) NOT NULL,
					object_id INT NOT NULL,
					object_key VARCHAR(200) NOT NULL,
					status VARCHAR(16) NOT NULL,
					changed INT NOT NULL,
					PRIMARY KEY (object_type, object_id)
				)
			");
			$this->db->query("CREATE NONCLUSTERED INDEX idx_search_index_state_status ON search_index_state (status)");
		}
	}
}
