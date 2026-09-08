<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Add dd_projects.data_type on catalogs that already had the table.
 *
 * Fresh CREATE from schema.dd.{mysql,sqlsrv}.sql already includes the column.
 * 20260829120001 skips CREATE when dd_projects exists and only adds
 * schema_version / submission, so upgraded SQL Server (and old MySQL)
 * catalogs can still miss data_type. Create-project then fails with 42S22/207.
 */
class Migration_Datadeposit_projects_data_type extends MY_Migration {

	public function up()
	{
		if (! $this->db->table_exists('dd_projects')) {
			log_message('info', 'Migration_Datadeposit_projects_data_type: dd_projects missing, skip');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->alter_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->alter_sqlsrv();
		} else {
			log_message('info', 'Migration_Datadeposit_projects_data_type: unsupported driver '.$driver);
			return;
		}

		$this->backfill();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function alter_mysql()
	{
		if ($this->db->field_exists('data_type', 'dd_projects')) {
			return;
		}

		$this->db->query("ALTER TABLE `dd_projects`
			ADD COLUMN `data_type` VARCHAR(20) DEFAULT NULL AFTER `created_on`");
	}

	private function alter_sqlsrv()
	{
		if ($this->db->field_exists('data_type', 'dd_projects')) {
			return;
		}

		$this->db->query("ALTER TABLE dd_projects ADD data_type varchar(20) NULL");
	}

	private function backfill()
	{
		if (! $this->db->field_exists('data_type', 'dd_projects')) {
			return;
		}

		if ($this->db->field_exists('project_type', 'dd_projects')) {
			$this->db->query("UPDATE dd_projects SET data_type = project_type
				WHERE (data_type IS NULL OR data_type = '')
				AND project_type IS NOT NULL AND project_type <> ''");
		}

		$this->db->query("UPDATE dd_projects SET data_type = 'survey'
			WHERE data_type IS NULL OR data_type = ''");
	}
}
