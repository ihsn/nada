<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: timeseries global value counts per study
 *
 * Creates table:
 *   - timeseries_value_counts: aggregated counts for (sid, dsd_id, component_name, code)
 */
class Migration_Create_timeseries_value_counts_table extends MY_Migration {

	public function up()
	{
		log_message('info', 'Migration_Create_timeseries_value_counts_table::up() called');

		if (!$this->db->table_exists('surveys') || !$this->db->table_exists('data_structures')) {
			log_message('info', 'Required tables missing (surveys/data_structures); skipping timeseries_value_counts migration');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, ['mysql', 'mysqli'])) {
			$this->up_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		} else {
			log_message('info', 'Migration_Create_timeseries_value_counts_table: unsupported driver ' . $driver . ', skipping');
			return;
		}

		log_message('info', 'Migration_Create_timeseries_value_counts_table completed successfully');
	}

	private function up_mysql()
	{
		if ($this->db->table_exists('timeseries_value_counts')) {
			log_message('info', 'timeseries_value_counts already exists; skipping');
			return;
		}

		$this->db->query("
			CREATE TABLE `timeseries_value_counts` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`sid` int(11) NOT NULL,
				`dsd_id` int(11) NOT NULL,
				`component_name` varchar(100) NOT NULL,
				`code` varchar(255) NOT NULL,
				`obs_count` int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `unq_tsvc_scope_value` (`sid`,`dsd_id`,`component_name`,`code`),
				KEY `idx_tsvc_scope` (`sid`,`dsd_id`,`component_name`),
				CONSTRAINT `fk_tsvc_sid` FOREIGN KEY (`sid`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
				CONSTRAINT `fk_tsvc_dsd` FOREIGN KEY (`dsd_id`) REFERENCES `data_structures` (`id`) ON DELETE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
		");

		log_message('info', 'Created timeseries_value_counts (MySQL)');
	}

	private function up_sqlsrv()
	{
		if ($this->db->table_exists('timeseries_value_counts')) {
			log_message('info', 'timeseries_value_counts already exists; skipping');
			return;
		}

		$this->db->query("
			CREATE TABLE timeseries_value_counts (
				id int NOT NULL IDENTITY(1,1),
				sid int NOT NULL,
				dsd_id int NOT NULL,
				component_name varchar(100) NOT NULL,
				code varchar(255) NOT NULL,
				obs_count int NOT NULL CONSTRAINT df_tsvc_obs_count DEFAULT 0,
				PRIMARY KEY (id),
				CONSTRAINT unq_tsvc_scope_value UNIQUE (sid, dsd_id, component_name, code),
				CONSTRAINT fk_tsvc_sid FOREIGN KEY (sid) REFERENCES surveys (id) ON DELETE CASCADE,
				CONSTRAINT fk_tsvc_dsd FOREIGN KEY (dsd_id) REFERENCES data_structures (id) ON DELETE CASCADE
			)
		");
		$this->db->query('CREATE INDEX idx_tsvc_scope ON timeseries_value_counts (sid, dsd_id, component_name)');

		log_message('info', 'Created timeseries_value_counts (SQL Server)');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
