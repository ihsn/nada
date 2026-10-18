<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Search index queue/state/meta plus catalog sidebar filter indexes.
 *
 * search_index_queue.priority orders the work: 0 for catalog changes, 1 for rows a sync queued, so a change made
 * during a long sync is not processed after all of it.
 *
 * search_index_meta has one row (added by 20260926120001_rename_search_engine_settings, once search_index_engine is
 * set) describing the rows in search_index_state / search_index_queue:
 *   engine            the engine they were written for (not always search_index_engine: the tables are cleared on
 *                     the first catalog change after the index engine changed)
 *   index_generation  nada-ai's study index generation they were written against (a new one = the index was recreated)
 *   nada_ai_url       the nada-ai they were written for (another URL = another nada-ai and index)
 *   tracked_since     when tracking for that engine and index started
 *   last_sync_at      when a full sync of that index last finished
 *   last_poll_at      when nada-ai last read the change queue
 *
 * Each step is idempotent.
 */
class Migration_Catalog_search_indexes extends MY_Migration {

	/**
	 * @var array<int, array{table: string, name: string, cols: string[]}>
	 */
	private $filter_indexes = array(
		array('table' => 'survey_countries', 'name' => 'idx_survey_countries_cid',      'cols' => array('cid', 'sid')),
		array('table' => 'survey_repos',     'name' => 'idx_survey_repos_repositoryid', 'cols' => array('repositoryid', 'sid')),
		array('table' => 'survey_repos',     'name' => 'idx_survey_repos_sid',          'cols' => array('sid')),
		array('table' => 'survey_facets',    'name' => 'idx_survey_facets_term_id',     'cols' => array('term_id', 'sid')),
		array('table' => 'survey_facets',    'name' => 'idx_survey_facets_sid',         'cols' => array('sid')),
		array('table' => 'survey_years',     'name' => 'idx_survey_years_year_sid',     'cols' => array('data_coll_year', 'sid')),
		array('table' => 'survey_tags',      'name' => 'idx_survey_tags_tag',           'cols' => array('tag', 'sid')),
		array('table' => 'region_countries', 'name' => 'idx_region_countries_region',   'cols' => array('region_id', 'country_id')),
	);

	public function up()
	{
		$this->create_search_index_schema();
		$this->add_queue_priority();
		$this->add_meta_nada_ai_url();
		$this->create_catalog_filter_indexes();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function create_search_index_schema()
	{
		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
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
						`priority` SMALLINT NOT NULL DEFAULT 0,
						PRIMARY KEY (`id`),
						UNIQUE KEY `uk_search_index_queue_object` (`object_type`, `object_id`),
						KEY `idx_search_index_queue_status_changed` (`status`, `changed`),
						KEY `idx_search_index_queue_status_priority` (`status`, `priority`, `changed`)
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

			if (!$this->db->table_exists('search_index_meta')) {
				$this->db->query("
					CREATE TABLE `search_index_meta` (
						`id` INT(11) NOT NULL,
						`engine` VARCHAR(32) NOT NULL,
						`index_generation` VARCHAR(64) DEFAULT NULL,
						`nada_ai_url` VARCHAR(255) DEFAULT NULL,
						`tracked_since` INT(11) NOT NULL,
						`last_sync_at` INT(11) DEFAULT NULL,
						`last_poll_at` INT(11) DEFAULT NULL,
						PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
				");
			}
			return;
		}

		if ($driver !== 'sqlsrv') {
			return;
		}

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
					priority SMALLINT NOT NULL CONSTRAINT df_search_index_queue_priority DEFAULT 0,
					PRIMARY KEY (id),
					CONSTRAINT uk_search_index_queue_object UNIQUE (object_type, object_id)
				)
			");
			$this->db->query("CREATE NONCLUSTERED INDEX idx_search_index_queue_status_changed ON search_index_queue (status, changed)");
			$this->db->query("CREATE NONCLUSTERED INDEX idx_search_index_queue_status_priority ON search_index_queue (status, priority, changed)");
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

		if (!$this->db->table_exists('search_index_meta')) {
			$this->db->query("
				CREATE TABLE search_index_meta (
					id INT NOT NULL,
					engine VARCHAR(32) NOT NULL,
					index_generation VARCHAR(64) NULL,
					nada_ai_url VARCHAR(255) NULL,
					tracked_since INT NOT NULL,
					last_sync_at INT NULL,
					last_poll_at INT NULL,
					PRIMARY KEY (id)
				)
			");
		}
	}

	/** search_index_meta created before the nada_ai_url column existed */
	private function add_meta_nada_ai_url()
	{
		if (!$this->db->table_exists('search_index_meta') || $this->db->field_exists('nada_ai_url', 'search_index_meta')) {
			return;
		}
		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'), true)) {
			$this->db->query("ALTER TABLE `search_index_meta` ADD COLUMN `nada_ai_url` VARCHAR(255) DEFAULT NULL AFTER `index_generation`");
		} elseif ($driver === 'sqlsrv') {
			$this->db->query("ALTER TABLE search_index_meta ADD nada_ai_url VARCHAR(255) NULL");
		}
		$this->forget_table_cache();
	}

	/** search_index_queue created before the priority column existed */
	private function add_queue_priority()
	{
		if (!$this->db->table_exists('search_index_queue') || $this->db->field_exists('priority', 'search_index_queue')) {
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'), true)) {
			$this->db->query("ALTER TABLE `search_index_queue` ADD COLUMN `priority` SMALLINT NOT NULL DEFAULT 0");
			$this->db->query("ALTER TABLE `search_index_queue` ADD INDEX `idx_search_index_queue_status_priority` (`status`, `priority`, `changed`)");
		} elseif ($driver === 'sqlsrv') {
			$this->db->query("ALTER TABLE search_index_queue ADD priority SMALLINT NOT NULL CONSTRAINT df_search_index_queue_priority DEFAULT 0");
			$this->db->query("CREATE NONCLUSTERED INDEX idx_search_index_queue_status_priority ON search_index_queue (status, priority, changed)");
		}
		$this->forget_table_cache();
	}

	private function create_catalog_filter_indexes()
	{
		$driver = $this->db->dbdriver;

		foreach ($this->filter_indexes as $index) {
			if (!$this->db->table_exists($index['table'])) {
				continue;
			}

			if (in_array($driver, array('mysql', 'mysqli'), true)) {
				$this->create_index_mysql($index);
			} elseif ($driver === 'sqlsrv') {
				$this->create_index_sqlsrv($index);
			}
		}
	}

	private function create_index_mysql(array $index)
	{
		if ($this->mysql_index_exists($index['table'], $index['name'])) {
			return;
		}

		foreach ($index['cols'] as $col) {
			if (!$this->db->field_exists($col, $index['table'])) {
				return;
			}
		}

		$cols = '`' . implode('`,`', $index['cols']) . '`';
		$this->db->query(
			'ALTER TABLE `' . $index['table'] . '` ADD INDEX `' . $index['name'] . '` (' . $cols . ')'
		);
	}

	private function create_index_sqlsrv(array $index)
	{
		if ($this->sqlsrv_index_exists($index['table'], $index['name'])) {
			return;
		}

		foreach ($index['cols'] as $col) {
			if (!$this->db->field_exists($col, $index['table'])) {
				return;
			}
		}

		$lead = array_shift($index['cols']);
		$included = $index['cols'];
		$sql = 'CREATE NONCLUSTERED INDEX ' . $index['name']
			. ' ON ' . $index['table'] . ' (' . $lead . ' ASC)';
		if (!empty($included)) {
			$sql .= ' INCLUDE (' . implode(',', $included) . ')';
		}
		$this->db->query($sql);
	}

	private function mysql_index_exists($table, $index_name)
	{
		$sql = 'SELECT COUNT(*) AS n FROM information_schema.STATISTICS
			WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?';
		$row = $this->db->query($sql, array($table, $index_name))->row_array();
		return !empty($row['n']);
	}

	private function sqlsrv_index_exists($table, $index_name)
	{
		$sql = 'SELECT COUNT(*) AS n FROM sys.indexes
			WHERE name = ? AND object_id = OBJECT_ID(?)';
		$row = $this->db->query($sql, array($index_name, $table))->row_array();
		return !empty($row['n']);
	}
}
