<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Per-locale title overlays for display templates.
 *
 * Primary-language titles stay on the layout tree. Extra locales are JSON maps
 * of node key → string, keyed by display_templates.id.
 */
class Migration_Display_template_translations extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('display_templates')) {
			log_message('info', 'Migration_Display_template_translations: display_templates missing, skip');
			return;
		}
		if ($this->db->table_exists('display_template_translations')) {
			log_message('info', 'Migration_Display_template_translations: table already exists, skip');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->up_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		} else {
			log_message('info', 'Migration_Display_template_translations: unsupported driver ' . $driver . ', skip');
		}
	}

	private function up_mysql()
	{
		$this->db->query("
			CREATE TABLE `display_template_translations` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`template_id` BIGINT UNSIGNED NOT NULL,
				`lang` VARCHAR(16) NOT NULL,
				`translations` JSON NOT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uk_display_template_translations_template_lang` (`template_id`, `lang`),
				CONSTRAINT `fk_display_template_translations_template`
					FOREIGN KEY (`template_id`) REFERENCES `display_templates` (`id`) ON DELETE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
		");
		log_message('info', 'Created display_template_translations (MySQL)');
	}

	private function up_sqlsrv()
	{
		$this->db->query("
			CREATE TABLE display_template_translations (
				id BIGINT NOT NULL IDENTITY(1,1),
				template_id BIGINT NOT NULL,
				lang VARCHAR(16) NOT NULL,
				translations NVARCHAR(MAX) NOT NULL,
				created_at DATETIME2 NOT NULL CONSTRAINT df_display_template_translations_created_at DEFAULT SYSDATETIME(),
				updated_at DATETIME2 NOT NULL CONSTRAINT df_display_template_translations_updated_at DEFAULT SYSDATETIME(),
				PRIMARY KEY (id),
				CONSTRAINT unq_display_template_translations_template_lang UNIQUE (template_id, lang),
				CONSTRAINT ck_display_template_translations_json CHECK (ISJSON(translations)=1),
				CONSTRAINT fk_display_template_translations_template
					FOREIGN KEY (template_id) REFERENCES display_templates (id) ON DELETE CASCADE
			)
		");
		log_message('info', 'Created display_template_translations (SQL Server)');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
