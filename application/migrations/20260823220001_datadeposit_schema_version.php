<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Expand dd_projects for the deposit JSON model.
 *
 * Adds schema_version (1 = legacy, 2 = new blobs) and submission JSON.
 * Reuses the existing unused metadata column for the study document.
 * Does not drop or rename dd_study / dd_citations.
 */
class Migration_Datadeposit_schema_version extends MY_Migration {

	public function up()
	{
		if (! $this->db->table_exists('dd_projects')) {
			log_message('info', 'Migration_Datadeposit_schema_version: dd_projects missing, skip');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->alter_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->alter_sqlsrv();
		} else {
			log_message('info', 'Migration_Datadeposit_schema_version: unsupported driver '.$driver);
		}
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function alter_mysql()
	{
		if (! $this->db->field_exists('schema_version', 'dd_projects')) {
			$this->db->query("ALTER TABLE `dd_projects`
				ADD COLUMN `schema_version` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `status`");
		}

		if (! $this->db->field_exists('submission', 'dd_projects')) {
			$this->db->query("ALTER TABLE `dd_projects`
				ADD COLUMN `submission` MEDIUMTEXT NULL AFTER `metadata`");
		}
	}

	private function alter_sqlsrv()
	{
		if (! $this->db->field_exists('schema_version', 'dd_projects')) {
			$this->db->query("ALTER TABLE dd_projects ADD schema_version TINYINT NOT NULL CONSTRAINT df_dd_projects_schema_version DEFAULT 1");
		}

		if (! $this->db->field_exists('submission', 'dd_projects')) {
			$this->db->query("ALTER TABLE dd_projects ADD submission NVARCHAR(MAX) NULL");
		}
	}
}
