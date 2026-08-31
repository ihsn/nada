<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Install data-deposit tables when missing (MySQL + SQL Server).
 *
 * Reads install/schema.dd.{mysql,sqlsrv}.sql. Then applies the v1-safe
 * column adds from 20260823220001 (schema_version default 1, submission)
 * for catalogs that already had the old schema.dd file.
 */
class Migration_Datadeposit_install_tables extends MY_Migration {

	public function up()
	{
		$driver = $this->db->dbdriver;
		if (!in_array($driver, array('mysql', 'mysqli', 'sqlsrv'), true)) {
			log_message('info', 'Migration_Datadeposit_install_tables: unsupported driver '.$driver);
			return;
		}

		if (!$this->db->table_exists('dd_projects')) {
			$sql_file = $this->schema_dd_path();
			log_message('info', 'Migration_Datadeposit_install_tables: executing '.basename($sql_file));
			$this->execute_sql_file($sql_file);
		} else {
			log_message('info', 'Migration_Datadeposit_install_tables: dd_projects exists, skip CREATE');
		}

		require_once APPPATH.'migrations/20260823220001_datadeposit_schema_version.php';
		$alter = new Migration_Datadeposit_schema_version();
		$alter->up();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function schema_dd_path()
	{
		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'), true)) {
			$driver = 'mysql';
		}

		$path = APPPATH.'../install/schema.dd.'.$driver.'.sql';
		if (!is_file($path)) {
			throw new Exception('SQL file not found: '.$path);
		}

		return $path;
	}
}
