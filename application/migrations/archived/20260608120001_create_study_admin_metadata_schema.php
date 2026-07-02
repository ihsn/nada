<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: create study_admin_metadata table
 *
 * Stores admin-only JSON metadata attached to catalog studies (API-only; not public metadata).
 */
class Migration_Create_study_admin_metadata_schema extends MY_Migration {

	public function up()
	{
		log_message('info', 'Migration_Create_study_admin_metadata_schema::up() called');

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->up_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		} else {
			log_message('info', 'Migration_Create_study_admin_metadata_schema: unsupported driver ' . $driver . ', skipping');
			return;
		}

		log_message('info', 'Migration_Create_study_admin_metadata_schema completed successfully');
	}

	private function up_mysql()
	{
		if (!$this->db->table_exists('study_admin_metadata')) {
			$this->db->query("
				CREATE TABLE `study_admin_metadata` (
					`sid` INT NOT NULL,
					`metadata` JSON NOT NULL,
					`created_by` INT UNSIGNED DEFAULT NULL,
					`changed_by` INT UNSIGNED DEFAULT NULL,
					`created` INT UNSIGNED DEFAULT NULL,
					`changed` INT UNSIGNED DEFAULT NULL,
					PRIMARY KEY (`sid`),
					KEY `idx_study_admin_metadata_changed` (`changed`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
			");
			log_message('info', 'Created study_admin_metadata (MySQL)');
		} else {
			log_message('info', 'study_admin_metadata already exists; skipping');
		}
	}

	private function up_sqlsrv()
	{
		if (!$this->db->table_exists('study_admin_metadata')) {
			$this->db->query("
				CREATE TABLE study_admin_metadata (
					sid INT NOT NULL,
					metadata NVARCHAR(MAX) NOT NULL,
					created_by INT NULL,
					changed_by INT NULL,
					created INT NULL,
					changed INT NULL,
					PRIMARY KEY (sid),
					CONSTRAINT ck_study_admin_metadata_metadata_isjson CHECK (ISJSON(metadata)=1)
				)
			");
			$this->db->query('CREATE INDEX idx_study_admin_metadata_changed ON study_admin_metadata (changed)');
			log_message('info', 'Created study_admin_metadata (SQL Server)');
		} else {
			log_message('info', 'study_admin_metadata already exists; skipping');
		}
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
