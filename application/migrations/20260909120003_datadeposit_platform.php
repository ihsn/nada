<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Install dd_* tables when missing, then add v2 columns on existing catalogs.
 * Schema DDL uses plain SQL files; duplicate errors are skipped on re-run.
 */
class Migration_Datadeposit_platform extends MY_Migration {

	public function up()
	{
		$this->execute_install_sql('nada56-filestore');
		$this->execute_install_sql('nada56-codelists-dsd');
		$this->install_tables_if_missing();
		$this->execute_install_sql('nada56-datadeposit-alter');
		$this->backfill_dd_projects_data_type();
		$this->deposit_max_upload_size_configuration();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function install_tables_if_missing()
	{
		$driver = $this->db->dbdriver;
		if (!in_array($driver, array('mysql', 'mysqli', 'sqlsrv'), true)) {
			return;
		}

		if ($this->db->table_exists('dd_projects')) {
			return;
		}

		$sql_driver = in_array($driver, array('mysql', 'mysqli'), true) ? 'mysql' : $driver;
		$path = APPPATH.'../install/schema.dd.'.$sql_driver.'.sql';
		if (!is_file($path)) {
			throw new Exception('SQL file not found: '.$path);
		}

		$this->execute_sql_file($path);
		$this->forget_table_cache();
	}

	private function backfill_dd_projects_data_type()
	{
		if (!$this->db->table_exists('dd_projects')
			|| !$this->db->field_exists('data_type', 'dd_projects')
		) {
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

	private function deposit_max_upload_size_configuration()
	{
		$this->insert_configuration_if_missing('deposit_max_upload_size', array(
			'value' => '2048',
			'label' => 'Data deposit max file size (MB)',
			'helptext' => 'Maximum size in megabytes for one file uploaded to a data-deposit project.',
			'item_group' => null,
		));
	}
}
