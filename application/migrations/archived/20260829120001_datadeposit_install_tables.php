<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Install data-deposit tables when missing (MySQL + SQL Server).
 *
 * Reads install/schema.dd.{mysql,sqlsrv}.sql. Column adds for catalogs
 * that already had dd_projects run as later archived steps in the
 * datadeposit_platform bundle (schema_version, data_type, metadata, resources).
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
