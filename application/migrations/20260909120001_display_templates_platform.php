<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Display templates, shipped cores/overlays, and leftover admin menu URL cleanup.
 * Each step is idempotent.
 */
class Migration_Display_templates_platform extends MY_Migration {

	public function up()
	{
		$this->create_display_templates_schema();
		$this->remove_visualization_survey_type();
		$this->file_backed_cores();
		$this->create_translations_table();
		$this->sync_shipped_overlays();
		$this->rename_survey_core_uid();
		$this->legacy_study_templates_configuration();
		$this->prefix_display_and_editor_template_uids();
		$this->migrate_site_menu_repositories_urls();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function create_display_templates_schema()
	{
		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			if (!$this->db->table_exists('display_templates')) {
				$this->assert_db_query($this->db->query("
					CREATE TABLE `display_templates` (
						`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
						`uid` VARCHAR(191) NOT NULL,
						`template_type` ENUM('system','custom','imported') NOT NULL DEFAULT 'custom',
						`source` ENUM('inline','file') NOT NULL DEFAULT 'inline',
						`data_type` VARCHAR(64) NOT NULL,
						`lang` VARCHAR(16) NOT NULL DEFAULT 'en',
						`name` VARCHAR(255) NOT NULL,
						`version` VARCHAR(50) DEFAULT NULL,
						`organization` VARCHAR(255) DEFAULT NULL,
						`author` VARCHAR(255) DEFAULT NULL,
						`description` TEXT DEFAULT NULL,
						`status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
						`template_json` JSON NULL,
						`file_path` VARCHAR(255) DEFAULT NULL,
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
				"), 'create display_templates');
				$this->forget_table_cache();
			}

			if (!$this->db->table_exists('display_templates_default')) {
				$this->assert_db_query($this->db->query("
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
				"), 'create display_templates_default');
				$this->forget_table_cache();
			}
			return;
		}

		if ($driver !== 'sqlsrv') {
			return;
		}

		if (!$this->db->table_exists('display_templates')) {
			$this->assert_db_query($this->db->query("
				CREATE TABLE display_templates (
					id BIGINT NOT NULL IDENTITY(1,1),
					uid VARCHAR(191) NOT NULL,
					template_type VARCHAR(20) NOT NULL CONSTRAINT df_display_templates_template_type DEFAULT 'custom',
					source VARCHAR(20) NOT NULL CONSTRAINT df_display_templates_source DEFAULT 'inline',
					data_type VARCHAR(64) NOT NULL,
					lang VARCHAR(16) NOT NULL CONSTRAINT df_display_templates_lang DEFAULT 'en',
					name VARCHAR(255) NOT NULL,
					version VARCHAR(50) NULL,
					organization VARCHAR(255) NULL,
					author VARCHAR(255) NULL,
					description NVARCHAR(MAX) NULL,
					status VARCHAR(20) NOT NULL CONSTRAINT df_display_templates_status DEFAULT 'draft',
					template_json NVARCHAR(MAX) NULL,
					file_path VARCHAR(255) NULL,
					is_deleted BIT NOT NULL CONSTRAINT df_display_templates_is_deleted DEFAULT 0,
					created_by INT NULL,
					changed_by INT NULL,
					created_at DATETIME2 NOT NULL CONSTRAINT df_display_templates_created_at DEFAULT SYSDATETIME(),
					updated_at DATETIME2 NOT NULL CONSTRAINT df_display_templates_updated_at DEFAULT SYSDATETIME(),
					PRIMARY KEY (id),
					CONSTRAINT ck_display_templates_template_type CHECK (template_type IN ('system','custom','imported')),
					CONSTRAINT ck_display_templates_source CHECK (source IN ('inline','file')),
					CONSTRAINT ck_display_templates_status CHECK (status IN ('draft','published','archived')),
					CONSTRAINT unq_display_templates_uid UNIQUE (uid),
					CONSTRAINT ck_display_templates_template_json_isjson CHECK (template_json IS NULL OR ISJSON(template_json)=1)
				)
			"), 'create display_templates');
			$this->forget_table_cache();
			$this->assert_db_query(
				$this->db->query('CREATE INDEX idx_display_templates_type_status ON display_templates (data_type, status)'),
				'index display_templates type_status'
			);
			$this->assert_db_query(
				$this->db->query('CREATE INDEX idx_display_templates_template_type ON display_templates (template_type)'),
				'index display_templates template_type'
			);
			$this->assert_db_query(
				$this->db->query('CREATE INDEX idx_display_templates_not_deleted ON display_templates (is_deleted, data_type)'),
				'index display_templates not_deleted'
			);
		}

		if (!$this->db->table_exists('display_templates_default')) {
			$this->assert_db_query($this->db->query("
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
			"), 'create display_templates_default');
			$this->forget_table_cache();
			$this->assert_db_query(
				$this->db->query('CREATE INDEX idx_display_default_template_uid ON display_templates_default (template_uid)'),
				'index display_templates_default template_uid'
			);
		}
	}

	private function remove_visualization_survey_type()
	{
		if ($this->db->table_exists('display_templates_default')
			&& $this->db->field_exists('data_type', 'display_templates_default')
		) {
			$this->db->where('data_type', 'visualization')->delete('display_templates_default');
		}

		if ($this->db->table_exists('display_templates')
			&& $this->db->field_exists('data_type', 'display_templates')
		) {
			$this->db->where('data_type', 'visualization')->delete('display_templates');
		}

		if ($this->db->table_exists('survey_types')
			&& $this->db->field_exists('code', 'survey_types')
		) {
			$this->db->where('code', 'visualization')->delete('survey_types');
		}
	}

	private function file_backed_cores()
	{
		if (!$this->db->table_exists('display_templates')) {
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			if (!$this->db->field_exists('source', 'display_templates')) {
				$this->assert_db_query($this->db->query("ALTER TABLE `display_templates`
					ADD COLUMN `source` ENUM('inline','file') NOT NULL DEFAULT 'inline' AFTER `template_type`"),
					'add display_templates.source');
			}
			if (!$this->db->field_exists('lang', 'display_templates')) {
				$this->assert_db_query($this->db->query("ALTER TABLE `display_templates`
					ADD COLUMN `lang` VARCHAR(16) NOT NULL DEFAULT 'en' AFTER `data_type`"),
					'add display_templates.lang');
			}
			if (!$this->db->field_exists('file_path', 'display_templates')) {
				$this->assert_db_query($this->db->query("ALTER TABLE `display_templates`
					ADD COLUMN `file_path` VARCHAR(255) DEFAULT NULL AFTER `template_json`"),
					'add display_templates.file_path');
			}
			$this->assert_db_query(
				$this->db->query("ALTER TABLE `display_templates` MODIFY `template_json` JSON NULL"),
				'make display_templates.template_json nullable'
			);
		} elseif ($driver === 'sqlsrv') {
			if (!$this->db->field_exists('source', 'display_templates')) {
				$this->assert_db_query(
					$this->db->query("ALTER TABLE display_templates ADD source VARCHAR(20) NOT NULL CONSTRAINT df_display_templates_source DEFAULT 'inline'"),
					'add display_templates.source'
				);
			}
			if (!$this->sqlsrv_check_exists('ck_display_templates_source')) {
				$this->assert_db_query(
					$this->db->query("ALTER TABLE display_templates ADD CONSTRAINT ck_display_templates_source CHECK (source IN ('inline','file'))"),
					'add display_templates.source check'
				);
			}
			if (!$this->db->field_exists('lang', 'display_templates')) {
				$this->assert_db_query(
					$this->db->query("ALTER TABLE display_templates ADD lang VARCHAR(16) NOT NULL CONSTRAINT df_display_templates_lang DEFAULT 'en'"),
					'add display_templates.lang'
				);
			}
			if (!$this->db->field_exists('file_path', 'display_templates')) {
				$this->assert_db_query(
					$this->db->query("ALTER TABLE display_templates ADD file_path VARCHAR(255) NULL"),
					'add display_templates.file_path'
				);
			}
			$this->drop_sqlsrv_constraint('display_templates', 'ck_display_templates_template_json_isjson');
			$this->assert_db_query(
				$this->db->query("ALTER TABLE display_templates ALTER COLUMN template_json NVARCHAR(MAX) NULL"),
				'make display_templates.template_json nullable'
			);
			if (!$this->sqlsrv_check_exists('ck_display_templates_template_json_isjson')) {
				$this->assert_db_query(
					$this->db->query("ALTER TABLE display_templates ADD CONSTRAINT ck_display_templates_template_json_isjson CHECK (template_json IS NULL OR ISJSON(template_json)=1)"),
					'add display_templates.template_json check'
				);
			}
		}

		$this->sync_shipped_cores();
	}

	private function create_translations_table()
	{
		if (!$this->db->table_exists('display_templates')
			|| $this->db->table_exists('display_template_translations')
		) {
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->assert_db_query($this->db->query("
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
			"), 'create display_template_translations');
			$this->forget_table_cache();
		} elseif ($driver === 'sqlsrv') {
			$this->assert_db_query($this->db->query("
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
			"), 'create display_template_translations');
			$this->forget_table_cache();
		}
	}

	private function sync_shipped_overlays()
	{
		if (!$this->db->table_exists('display_templates')
			|| !$this->db->table_exists('display_template_translations')) {
			return;
		}
		$this->sync_shipped_cores();
	}

	private function rename_survey_core_uid()
	{
		if (!$this->db->table_exists('display_templates')) {
			return;
		}

		$old = 'microdata-system-en';
		$new = 'survey-system-en';
		$display_file = 'templates/display/survey_display_template.json';

		$this->db->from('display_templates');
		$this->db->where('uid', $new);
		$this->db->where('is_deleted', 0);
		$new_row = $this->db->get()->row_array();

		$this->db->from('display_templates');
		$this->db->where('uid', $old);
		$this->db->where('is_deleted', 0);
		$old_row = $this->db->get()->row_array();

		if ($old_row && !$new_row) {
			$file_path = isset($old_row['file_path']) ? (string) $old_row['file_path'] : '';
			$is_display_core = ($file_path === $display_file)
				|| (isset($old_row['source']) && $old_row['source'] === 'file'
					&& isset($old_row['data_type']) && $old_row['data_type'] === 'survey');
			if ($is_display_core) {
				$this->db->where('id', (int) $old_row['id']);
				$this->db->update('display_templates', array('uid' => $new));
			}
		}

		if ($this->db->table_exists('display_templates_default')) {
			$this->db->where('data_type', 'survey');
			$this->db->where('template_uid', $old);
			$this->db->update('display_templates_default', array('template_uid' => $new));
		}

		$this->sync_shipped_cores();
	}

	private function legacy_study_templates_configuration()
	{
		if (!$this->db->table_exists('configurations')) {
			return;
		}

		$this->load->helper('display_template');
		$types = display_template_normalize_legacy_study_types(
			display_template_legacy_study_templates_raw()
		);

		$this->insert_configuration_if_missing('legacy_study_templates', array(
			'value' => json_encode($types),
			'label' => 'Study description layout',
			'helptext' => 'JSON list of catalog types that still use PHP metadata_templates views.',
			'item_group' => null,
		));
	}

	private function prefix_display_and_editor_template_uids()
	{
		if (!$this->db->table_exists('display_templates')) {
			return;
		}

		foreach ($this->uid_map() as $old => $new) {
			$this->remap_uid($old, $new);
		}

		foreach ($this->file_path_map() as $old => $new) {
			$this->db->where('file_path', $old);
			$this->db->update('display_templates', array('file_path' => $new));
		}

		if ($this->db->table_exists('display_templates_default')) {
			foreach ($this->uid_map() as $old => $new) {
				$this->db->where('template_uid', $old);
				$this->db->update('display_templates_default', array('template_uid' => $new));
			}
		}

		$this->sync_shipped_cores();
	}

	private function migrate_site_menu_repositories_urls()
	{
		if (!$this->db->table_exists('site_menu')
			|| !$this->db->field_exists('url', 'site_menu')
			|| !$this->db->field_exists('id', 'site_menu')
		) {
			return;
		}

		$this->db->where('url', 'admin/repositories');
		$this->db->update('site_menu', array('url' => 'admin/collections'));

		$this->db->like('url', 'admin/repositories/', 'after');
		$query = $this->db->get('site_menu');
		if ($query === false) {
			log_message('error', __CLASS__.': site_menu LIKE query failed');
			return;
		}

		foreach ($query->result_array() as $row) {
			$new_url = 'admin/collections/' . substr($row['url'], strlen('admin/repositories/'));
			$this->db->where('id', (int) $row['id']);
			$this->db->update('site_menu', array('url' => $new_url));
		}
	}

	private function sync_shipped_cores()
	{
		$this->load->helper('display_template');
		$this->load->model('Display_template_model');
		$this->Display_template_model->sync_shipped_cores();
	}

	private function sqlsrv_check_exists($name)
	{
		$sql = "SELECT 1 FROM sys.check_constraints WHERE name = " . $this->db->escape($name);
		$q = $this->db->query($sql);
		return $q && $q->num_rows() > 0;
	}

	private function drop_sqlsrv_constraint($table, $name)
	{
		if ($this->sqlsrv_check_exists($name)) {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name),
				'drop constraint '.$name
			);
		}
	}

	private function remap_uid($old, $new)
	{
		$this->db->from('display_templates');
		$this->db->where('uid', $old);
		$this->db->where('is_deleted', 0);
		$old_row = $this->db->get()->row_array();

		$this->db->from('display_templates');
		$this->db->where('uid', $new);
		$this->db->where('is_deleted', 0);
		$new_row = $this->db->get()->row_array();

		if ($old_row && !$new_row) {
			$this->db->where('id', (int) $old_row['id']);
			$this->db->update('display_templates', array('uid' => $new));
			return;
		}

		if ($old_row && $new_row) {
			$source = isset($old_row['source']) ? $old_row['source'] : '';
			$type = isset($old_row['template_type']) ? $old_row['template_type'] : '';
			if ($source === 'file' || $type === 'system') {
				$this->db->where('id', (int) $old_row['id']);
				$this->db->update('display_templates', array('is_deleted' => 1));
			}
		}
	}

	private function uid_map()
	{
		$old = array(
			'survey-system-en',
			'timeseries-system-en',
			'timeseries-db-system-en',
			'script-system-en',
			'geospatial-system-en',
			'geospatial-gemini-inspire-en',
			'document-system-en',
			'document-system-es',
			'table-system-en',
			'image-system-en',
			'image-system-dcmi',
			'image-system-iptc',
			'video-system-en',
			'resource-system-en',
			'microdata-system-es',
			'6740f5f920502baf3f6cbcaa5c113deeen',
			'232ea3aaece0cdf1db157f797f6b92e5fr',
			'6740f5f920502baf3f6cbcaa5c113uzbek',
			'8603d94e27bccc2bdad1e00dbbf0fe32en',
			'b576eb7519fe8e761a239f9d36f032c3fr',
			'f3e2d1c8c494be27bc229463a265a33d',
			'776d77524fe8130f520035fb9b077d82',
			'd0e54377885c64c360259b65398e319den',
			'd31789f2d6dcdf3c7dc07a5729d09ac9fr',
			'2f62a6b2716ab55b4426005abdbe1600en',
			'4915f93564fbde26945dd9022b9d7fc1fr',
			'4b56b6c4ec82324c7c2865ad61c4f2c0en',
			'9454eee369c79c65cdc0b4ee23aed4e8fr',
			'0d8e25111cee667a4b4636088cdb33e3',
			'6192765f2dbddb6bd93f3f92a29129d3fr',
			'2bfd3fb47e291331a43e949e9e38675een',
			'21769091754bb7c998a1caf0fa75800cfr',
			'1f899824931c133f162f584c928d4256',
			'e3e5a1d8bd0338c1b3a5d6bfff8e5517',
			'e6670ad469892a3871ee0e5d47cf243den',
			'453a8bf9f800465f3cb8dc37699cb574',
		);

		$map = array();
		foreach ($old as $uid) {
			$map[$uid] = 'display-'.$uid;
		}
		return $map;
	}

	private function file_path_map()
	{
		return array(
			'templates/display/survey_display_template.json' => 'templates/display/display_survey_template.json',
			'templates/display/timeseries_display_template.json' => 'templates/display/display_timeseries_template.json',
			'templates/display/timeseriesdb_display_template.json' => 'templates/display/display_timeseries-db_template.json',
			'templates/display/script_display_template.json' => 'templates/display/display_script_template.json',
			'templates/display/geospatial_display_template.json' => 'templates/display/display_geospatial_template.json',
			'templates/display/document_display_template.json' => 'templates/display/display_document_template.json',
			'templates/display/table_display_template.json' => 'templates/display/display_table_template.json',
			'templates/display/image_display_template.json' => 'templates/display/display_image_template.json',
			'templates/display/video_display_template.json' => 'templates/display/display_video_template.json',
			'templates/display/resource_display_template.json' => 'templates/display/display_resource_template.json',
			'templates/editor/survey_template_ihsn_2.5_v1.json' => 'templates/editor/edit_survey_template_ihsn_2.5_v1.json',
			'templates/editor/survey_template_ihsn_2.5_v1_fr.json' => 'templates/editor/edit_survey_template_ihsn_2.5_v1_fr.json',
			'templates/editor/survey_template_es.json' => 'templates/editor/edit_survey_template_es.json',
			'templates/editor/microdata_template_uzbek.json' => 'templates/editor/edit_microdata_template_uzbek.json',
			'templates/editor/timeseries_template_ihsn.json' => 'templates/editor/edit_timeseries_template_ihsn.json',
			'templates/editor/timeseries_template_ihsn_fr.json' => 'templates/editor/edit_timeseries_template_ihsn_fr.json',
			'templates/editor/timeseries-db_template_ihsn.json' => 'templates/editor/edit_timeseries-db_template_ihsn.json',
			'templates/editor/timeseries-db_template_ihsn_fr.json' => 'templates/editor/edit_timeseries-db_template_ihsn_fr.json',
			'templates/editor/script_template_ihsn.json' => 'templates/editor/edit_script_template_ihsn.json',
			'templates/editor/script_template_ihsn_fr.json' => 'templates/editor/edit_script_template_ihsn_fr.json',
			'templates/editor/geospatial_form_template_gemini.json' => 'templates/editor/edit_geospatial_form_template_gemini.json',
			'templates/editor/document_form_template_es.json' => 'templates/editor/edit_document_form_template_es.json',
			'templates/editor/document_template_ihsn.json' => 'templates/editor/edit_document_template_ihsn.json',
			'templates/editor/document_template_ihsn_fr.json' => 'templates/editor/edit_document_template_ihsn_fr.json',
			'templates/editor/table_template_ihsn.json' => 'templates/editor/edit_table_template_ihsn.json',
			'templates/editor/table_template_ihsn_fr.json' => 'templates/editor/edit_table_template_ihsn_fr.json',
			'templates/editor/image_dcmi_form_template.json' => 'templates/editor/edit_image_dcmi_form_template.json',
			'templates/editor/image_iptc_form_template.json' => 'templates/editor/edit_image_iptc_form_template.json',
			'templates/editor/image_dcmi_template_ihsn.json' => 'templates/editor/edit_image_dcmi_template_ihsn.json',
			'templates/editor/image_dcmi_template_ihsn_fr.json' => 'templates/editor/edit_image_dcmi_template_ihsn_fr.json',
			'templates/editor/image_iptc_template_ihsn.json' => 'templates/editor/edit_image_iptc_template_ihsn.json',
			'templates/editor/image_iptc_template_ihsn_fr.json' => 'templates/editor/edit_image_iptc_template_ihsn_fr.json',
			'templates/editor/video_template_ihsn.json' => 'templates/editor/edit_video_template_ihsn.json',
			'templates/editor/video_template_ihsn_fr.json' => 'templates/editor/edit_video_template_ihsn_fr.json',
			'templates/editor/resource_template_ihsn.json' => 'templates/editor/edit_resource_template_ihsn.json',
			'templates/editor/resource_template_ihsn_fr.json' => 'templates/editor/edit_resource_template_ihsn_fr.json',
		);
	}
}
