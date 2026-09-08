<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Add dd_projects.metadata on catalogs that already had the table.
 *
 * Fresh CREATE from schema.dd.{mysql,sqlsrv}.sql already includes the column.
 * 20260823220001 assumed an unused metadata column and only added
 * schema_version / submission. Existing SQL Server (and some MySQL)
 * catalogs never had it, so import fails with 42S22/207.
 */
class Migration_Datadeposit_projects_metadata extends MY_Migration {

	public function up()
	{
		if (! $this->db->table_exists('dd_projects')) {
			log_message('info', 'Migration_Datadeposit_projects_metadata: dd_projects missing, skip');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->alter_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->alter_sqlsrv();
		} else {
			log_message('info', 'Migration_Datadeposit_projects_metadata: unsupported driver '.$driver);
		}
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function alter_mysql()
	{
		if ($this->db->field_exists('metadata', 'dd_projects')) {
			return;
		}

		if ($this->db->field_exists('requested_when', 'dd_projects')) {
			$this->db->query("ALTER TABLE `dd_projects`
				ADD COLUMN `metadata` MEDIUMTEXT NULL AFTER `requested_when`");
			return;
		}

		$this->db->query("ALTER TABLE `dd_projects`
			ADD COLUMN `metadata` MEDIUMTEXT NULL");
	}

	private function alter_sqlsrv()
	{
		if ($this->db->field_exists('metadata', 'dd_projects')) {
			return;
		}

		$this->db->query("ALTER TABLE dd_projects ADD metadata varchar(max) NULL");
	}
}
