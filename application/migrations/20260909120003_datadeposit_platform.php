<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Install dd_* tables when missing, then add v2 columns on existing catalogs.
 * Each step is idempotent.
 */
class Migration_Datadeposit_platform extends MY_Migration {

	public function up()
	{
		$this->install_tables_if_missing();
		$this->add_schema_version_and_submission();
		$this->add_data_type();
		$this->add_metadata();
		$this->add_resource_columns();
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
	}

	private function add_schema_version_and_submission()
	{
		if (!$this->db->table_exists('dd_projects')) {
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			if (!$this->db->field_exists('schema_version', 'dd_projects')) {
				$this->db->query("ALTER TABLE `dd_projects`
					ADD COLUMN `schema_version` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `status`");
			}
			if (!$this->db->field_exists('submission', 'dd_projects')) {
				if ($this->db->field_exists('metadata', 'dd_projects')) {
					$this->db->query("ALTER TABLE `dd_projects`
						ADD COLUMN `submission` MEDIUMTEXT NULL AFTER `metadata`");
				} else {
					$this->db->query("ALTER TABLE `dd_projects`
						ADD COLUMN `submission` MEDIUMTEXT NULL");
				}
			}
			return;
		}

		if ($driver !== 'sqlsrv') {
			return;
		}

		if (!$this->db->field_exists('schema_version', 'dd_projects')) {
			$this->db->query("ALTER TABLE dd_projects ADD schema_version TINYINT NOT NULL CONSTRAINT df_dd_projects_schema_version DEFAULT 1");
		}
		if (!$this->db->field_exists('submission', 'dd_projects')) {
			$this->db->query("ALTER TABLE dd_projects ADD submission NVARCHAR(MAX) NULL");
		}
	}

	private function add_data_type()
	{
		if (!$this->db->table_exists('dd_projects')) {
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			if (!$this->db->field_exists('data_type', 'dd_projects')) {
				$this->db->query("ALTER TABLE `dd_projects`
					ADD COLUMN `data_type` VARCHAR(20) DEFAULT NULL AFTER `created_on`");
			}
		} elseif ($driver === 'sqlsrv') {
			if (!$this->db->field_exists('data_type', 'dd_projects')) {
				$this->db->query("ALTER TABLE dd_projects ADD data_type varchar(20) NULL");
			}
		} else {
			return;
		}

		if (!$this->db->field_exists('data_type', 'dd_projects')) {
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

	private function add_metadata()
	{
		if (!$this->db->table_exists('dd_projects')
			|| $this->db->field_exists('metadata', 'dd_projects')
		) {
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			if ($this->db->field_exists('requested_when', 'dd_projects')) {
				$this->db->query("ALTER TABLE `dd_projects`
					ADD COLUMN `metadata` MEDIUMTEXT NULL AFTER `requested_when`");
				return;
			}
			$this->db->query("ALTER TABLE `dd_projects` ADD COLUMN `metadata` MEDIUMTEXT NULL");
			return;
		}

		if ($driver === 'sqlsrv') {
			$this->db->query("ALTER TABLE dd_projects ADD metadata varchar(max) NULL");
		}
	}

	private function add_resource_columns()
	{
		if (!$this->db->table_exists('dd_project_resources')) {
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			if (!$this->db->field_exists('dctype', 'dd_project_resources')) {
				$this->db->query("ALTER TABLE `dd_project_resources`
					ADD COLUMN `dctype` VARCHAR(100) DEFAULT NULL AFTER `filename`");
			}
			if (!$this->db->field_exists('dcformat', 'dd_project_resources')) {
				$after = $this->db->field_exists('dctype', 'dd_project_resources') ? 'dctype' : 'filename';
				$this->db->query("ALTER TABLE `dd_project_resources`
					ADD COLUMN `dcformat` VARCHAR(100) DEFAULT NULL AFTER `{$after}`");
			}
			if (!$this->db->field_exists('filesize', 'dd_project_resources')) {
				$after = $this->db->field_exists('dcformat', 'dd_project_resources') ? 'dcformat' : 'filename';
				$this->db->query("ALTER TABLE `dd_project_resources`
					ADD COLUMN `filesize` DOUBLE DEFAULT NULL AFTER `{$after}`");
			}
			return;
		}

		if ($driver !== 'sqlsrv') {
			return;
		}

		if (!$this->db->field_exists('dctype', 'dd_project_resources')) {
			$this->db->query("ALTER TABLE dd_project_resources ADD dctype varchar(100) NULL");
		}
		if (!$this->db->field_exists('dcformat', 'dd_project_resources')) {
			$this->db->query("ALTER TABLE dd_project_resources ADD dcformat varchar(100) NULL");
		}
		if (!$this->db->field_exists('filesize', 'dd_project_resources')) {
			$this->db->query("ALTER TABLE dd_project_resources ADD filesize float NULL");
		}
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
