<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: create display templates schema
 *
 * Creates tables:
 *   - display_templates
 *   - display_templates_default
 */
class Migration_Create_display_templates_schema extends MY_Migration {

	public function up()
	{
		log_message('info', 'Migration_Create_display_templates_schema::up() called');

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->up_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		} else {
			log_message('info', 'Migration_Create_display_templates_schema: unsupported driver ' . $driver . ', skipping');
			return;
		}

		log_message('info', 'Migration_Create_display_templates_schema completed successfully');
	}

	private function up_mysql()
	{
		if (!$this->db->table_exists('display_templates')) {
			$this->db->query("
				CREATE TABLE `display_templates` (
					`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					`uid` VARCHAR(191) NOT NULL,
					`template_type` ENUM('system','custom','imported') NOT NULL DEFAULT 'custom',
					`data_type` VARCHAR(64) NOT NULL,
					`name` VARCHAR(255) NOT NULL,
					`version` VARCHAR(50) DEFAULT NULL,
					`organization` VARCHAR(255) DEFAULT NULL,
					`author` VARCHAR(255) DEFAULT NULL,
					`description` TEXT DEFAULT NULL,
					`status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
					`template_json` JSON NOT NULL,
					`is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
					`created_by` INT UNSIGNED DEFAULT NULL,
					`changed_by` INT UNSIGNED DEFAULT NULL,
					`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					PRIMARY KEY (`id`),
					UNIQUE KEY `uk_display_templates_uid` (`uid`),
					KEY `idx_display_templates_type_status` (`data_type`, `status`),
					KEY `idx_display_templates_template_type` (`template_type`),
					KEY `idx_display_templates_not_deleted` (`is_deleted`, `data_type`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
			");
			log_message('info', 'Created display_templates (MySQL)');
		} else {
			log_message('info', 'display_templates already exists; skipping');
		}

		if (!$this->db->table_exists('display_templates_default')) {
			$this->db->query("
				CREATE TABLE `display_templates_default` (
					`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					`data_type` VARCHAR(64) NOT NULL,
					`template_uid` VARCHAR(191) NOT NULL,
					`created_by` INT UNSIGNED DEFAULT NULL,
					`updated_by` INT UNSIGNED DEFAULT NULL,
					`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					PRIMARY KEY (`id`),
					UNIQUE KEY `uk_display_default_type` (`data_type`),
					KEY `idx_display_default_template_uid` (`template_uid`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
			");
			log_message('info', 'Created display_templates_default (MySQL)');
		} else {
			log_message('info', 'display_templates_default already exists; skipping');
		}
	}

	private function up_sqlsrv()
	{
		if (!$this->db->table_exists('display_templates')) {
			$this->db->query("
				CREATE TABLE display_templates (
					id BIGINT NOT NULL IDENTITY(1,1),
					uid VARCHAR(191) NOT NULL,
					template_type VARCHAR(20) NOT NULL CONSTRAINT df_display_templates_template_type DEFAULT 'custom',
					data_type VARCHAR(64) NOT NULL,
					name VARCHAR(255) NOT NULL,
					version VARCHAR(50) NULL,
					organization VARCHAR(255) NULL,
					author VARCHAR(255) NULL,
					description NVARCHAR(MAX) NULL,
					status VARCHAR(20) NOT NULL CONSTRAINT df_display_templates_status DEFAULT 'draft',
					template_json NVARCHAR(MAX) NOT NULL,
					is_deleted BIT NOT NULL CONSTRAINT df_display_templates_is_deleted DEFAULT 0,
					created_by INT NULL,
					changed_by INT NULL,
					created_at DATETIME2 NOT NULL CONSTRAINT df_display_templates_created_at DEFAULT SYSDATETIME(),
					updated_at DATETIME2 NOT NULL CONSTRAINT df_display_templates_updated_at DEFAULT SYSDATETIME(),
					PRIMARY KEY (id),
					CONSTRAINT ck_display_templates_template_type CHECK (template_type IN ('system','custom','imported')),
					CONSTRAINT ck_display_templates_status CHECK (status IN ('draft','published','archived')),
					CONSTRAINT unq_display_templates_uid UNIQUE (uid),
					CONSTRAINT ck_display_templates_template_json_isjson CHECK (ISJSON(template_json)=1)
				)
			");
			$this->db->query('CREATE INDEX idx_display_templates_type_status ON display_templates (data_type, status)');
			$this->db->query('CREATE INDEX idx_display_templates_template_type ON display_templates (template_type)');
			$this->db->query('CREATE INDEX idx_display_templates_not_deleted ON display_templates (is_deleted, data_type)');
			log_message('info', 'Created display_templates (SQL Server)');
		} else {
			log_message('info', 'display_templates already exists; skipping');
		}

		if (!$this->db->table_exists('display_templates_default')) {
			$this->db->query("
				CREATE TABLE display_templates_default (
					id BIGINT NOT NULL IDENTITY(1,1),
					data_type VARCHAR(64) NOT NULL,
					template_uid VARCHAR(191) NOT NULL,
					created_by INT NULL,
					updated_by INT NULL,
					created_at DATETIME2 NOT NULL CONSTRAINT df_display_templates_default_created_at DEFAULT SYSDATETIME(),
					updated_at DATETIME2 NOT NULL CONSTRAINT df_display_templates_default_updated_at DEFAULT SYSDATETIME(),
					PRIMARY KEY (id),
					CONSTRAINT unq_display_default_type UNIQUE (data_type)
				)
			");
			$this->db->query('CREATE INDEX idx_display_default_template_uid ON display_templates_default (template_uid)');
			log_message('info', 'Created display_templates_default (SQL Server)');
		} else {
			log_message('info', 'display_templates_default already exists; skipping');
		}
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}

