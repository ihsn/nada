<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Add dd_project_resources.filesize / dctype / dcformat when missing.
 *
 * Fresh CREATE from schema.dd.{mysql,sqlsrv}.sql already includes them.
 * 20260829120001 skips CREATE when dd_projects exists, so upgraded
 * catalogs can miss filesize (Vue upload then fails with 42S22/207).
 */
class Migration_Datadeposit_project_resources_columns extends MY_Migration {

	public function up()
	{
		if (! $this->db->table_exists('dd_project_resources')) {
			log_message('info', 'Migration_Datadeposit_project_resources_columns: dd_project_resources missing, skip');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->alter_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->alter_sqlsrv();
		} else {
			log_message('info', 'Migration_Datadeposit_project_resources_columns: unsupported driver '.$driver);
		}
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function alter_mysql()
	{
		if (! $this->db->field_exists('dctype', 'dd_project_resources')) {
			$this->db->query("ALTER TABLE `dd_project_resources`
				ADD COLUMN `dctype` VARCHAR(100) DEFAULT NULL AFTER `filename`");
		}

		if (! $this->db->field_exists('dcformat', 'dd_project_resources')) {
			$after = $this->db->field_exists('dctype', 'dd_project_resources') ? 'dctype' : 'filename';
			$this->db->query("ALTER TABLE `dd_project_resources`
				ADD COLUMN `dcformat` VARCHAR(100) DEFAULT NULL AFTER `{$after}`");
		}

		if (! $this->db->field_exists('filesize', 'dd_project_resources')) {
			$after = $this->db->field_exists('dcformat', 'dd_project_resources') ? 'dcformat' : 'filename';
			$this->db->query("ALTER TABLE `dd_project_resources`
				ADD COLUMN `filesize` DOUBLE DEFAULT NULL AFTER `{$after}`");
		}
	}

	private function alter_sqlsrv()
	{
		if (! $this->db->field_exists('dctype', 'dd_project_resources')) {
			$this->db->query("ALTER TABLE dd_project_resources ADD dctype varchar(100) NULL");
		}

		if (! $this->db->field_exists('dcformat', 'dd_project_resources')) {
			$this->db->query("ALTER TABLE dd_project_resources ADD dcformat varchar(100) NULL");
		}

		if (! $this->db->field_exists('filesize', 'dd_project_resources')) {
			$this->db->query("ALTER TABLE dd_project_resources ADD filesize float NULL");
		}
	}
}
