<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * File-backed display cores: registry rows in display_templates pointing at JSON files.
 *
 * Layout stays on disk (git). Runtime catalog no longer merges config/display_templates.php.
 */
class Migration_Display_templates_file_backed_cores extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('display_templates')) {
			log_message('info', 'Migration_Display_templates_file_backed_cores: display_templates missing, skip');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->alter_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->alter_sqlsrv();
		} else {
			log_message('info', 'Migration_Display_templates_file_backed_cores: unsupported driver ' . $driver . ', skip schema');
		}

		$this->load->helper('display_template');
		$this->load->model('Display_template_model');
		$this->Display_template_model->sync_shipped_cores();
	}

	private function alter_mysql()
	{
		if (!$this->db->field_exists('source', 'display_templates')) {
			$this->db->query("ALTER TABLE `display_templates`
				ADD COLUMN `source` ENUM('inline','file') NOT NULL DEFAULT 'inline' AFTER `template_type`");
		}
		if (!$this->db->field_exists('lang', 'display_templates')) {
			$this->db->query("ALTER TABLE `display_templates`
				ADD COLUMN `lang` VARCHAR(16) NOT NULL DEFAULT 'en' AFTER `data_type`");
		}
		if (!$this->db->field_exists('file_path', 'display_templates')) {
			$this->db->query("ALTER TABLE `display_templates`
				ADD COLUMN `file_path` VARCHAR(255) DEFAULT NULL AFTER `template_json`");
		}

		$this->db->query("ALTER TABLE `display_templates` MODIFY `template_json` JSON NULL");
	}

	private function alter_sqlsrv()
	{
		if (!$this->db->field_exists('source', 'display_templates')) {
			$this->db->query("ALTER TABLE display_templates ADD source VARCHAR(20) NOT NULL CONSTRAINT df_display_templates_source DEFAULT 'inline'");
			$this->db->query("ALTER TABLE display_templates ADD CONSTRAINT ck_display_templates_source CHECK (source IN ('inline','file'))");
		}
		if (!$this->db->field_exists('lang', 'display_templates')) {
			$this->db->query("ALTER TABLE display_templates ADD lang VARCHAR(16) NOT NULL CONSTRAINT df_display_templates_lang DEFAULT 'en'");
		}
		if (!$this->db->field_exists('file_path', 'display_templates')) {
			$this->db->query("ALTER TABLE display_templates ADD file_path VARCHAR(255) NULL");
		}

		$this->drop_sqlsrv_constraint('display_templates', 'ck_display_templates_template_json_isjson');
		$this->db->query("ALTER TABLE display_templates ALTER COLUMN template_json NVARCHAR(MAX) NULL");
		$this->db->query("ALTER TABLE display_templates ADD CONSTRAINT ck_display_templates_template_json_isjson CHECK (template_json IS NULL OR ISJSON(template_json)=1)");
	}

	private function drop_sqlsrv_constraint($table, $name)
	{
		$sql = "SELECT 1 FROM sys.check_constraints WHERE name = " . $this->db->escape($name);
		$q = $this->db->query($sql);
		if ($q && $q->num_rows() > 0) {
			$this->db->query('ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name);
		}
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
