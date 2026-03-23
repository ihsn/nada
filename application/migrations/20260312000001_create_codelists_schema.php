<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: Generic codelists schema
 *
 * Creates tables: codelists, codelist_item, codelist_item_translation,
 * codelist_group, codelist_group_item, codelist_group_translation.
 * Includes parent_id on items (for hierarchy, UI later) and sort_order
 * on items, groups, and group_item.
 * Seeds the 'dctypes' codelist from existing dctypes/dctype_translations
 * and default groups from external_resources config.
 */
class Migration_Create_codelists_schema extends MY_Migration {

	public function up()
	{
		log_message('info', 'Migration_Create_codelists_schema::up() called');

		$driver = $this->db->dbdriver;
		$is_mysql = in_array($driver, ['mysql', 'mysqli']);

		$this->create_codelists_table($is_mysql);
		$this->create_codelist_item_table($is_mysql);
		$this->create_codelist_item_translation_table($is_mysql);
		$this->create_codelist_group_table($is_mysql);
		$this->create_codelist_group_item_table($is_mysql);
		$this->create_codelist_group_translation_table($is_mysql);

		$this->seed_dctypes_codelist();

		log_message('info', 'Migration_Create_codelists_schema completed successfully');
	}

	private function create_codelists_table($is_mysql)
	{
		if ($this->db->table_exists('codelists')) {
			log_message('info', 'codelists already exists');
			return;
		}
		if ($is_mysql) {
			$this->db->query("
				CREATE TABLE `codelists` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`name` VARCHAR(64) NOT NULL,
					`description` VARCHAR(255) DEFAULT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `unq_codelists_name` (`name`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8
			");
		} else {
			$this->db->query("
				CREATE TABLE codelists (
					id INT NOT NULL IDENTITY(1,1),
					name VARCHAR(64) NOT NULL,
					description VARCHAR(255) DEFAULT NULL,
					PRIMARY KEY (id),
					CONSTRAINT unq_codelists_name UNIQUE (name)
				)
			");
		}
		log_message('info', 'Created codelists table');
	}

	private function create_codelist_item_table($is_mysql)
	{
		if ($this->db->table_exists('codelist_item')) {
			log_message('info', 'codelist_item already exists');
			return;
		}
		if ($is_mysql) {
			$this->db->query("
				CREATE TABLE `codelist_item` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`codelist_id` INT(11) NOT NULL,
					`parent_id` INT(11) DEFAULT NULL,
					`code` VARCHAR(64) NOT NULL,
					`title` VARCHAR(255) DEFAULT NULL,
					`sort_order` INT(11) NOT NULL DEFAULT 0,
					PRIMARY KEY (`id`),
					UNIQUE KEY `unq_codelist_item_code` (`codelist_id`, `code`),
					KEY `idx_codelist_item_parent` (`parent_id`),
					KEY `idx_codelist_item_sort` (`codelist_id`, `sort_order`),
					CONSTRAINT `fk_codelist_item_codelist` FOREIGN KEY (`codelist_id`) REFERENCES `codelists` (`id`) ON DELETE CASCADE,
					CONSTRAINT `fk_codelist_item_parent` FOREIGN KEY (`parent_id`) REFERENCES `codelist_item` (`id`) ON DELETE SET NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8
			");
		} else {
			$this->db->query("
				CREATE TABLE codelist_item (
					id INT NOT NULL IDENTITY(1,1),
					codelist_id INT NOT NULL,
					parent_id INT DEFAULT NULL,
					code VARCHAR(64) NOT NULL,
					title VARCHAR(255) DEFAULT NULL,
					sort_order INT NOT NULL DEFAULT 0,
					PRIMARY KEY (id),
					CONSTRAINT unq_codelist_item_code UNIQUE (codelist_id, code),
					CONSTRAINT fk_codelist_item_codelist FOREIGN KEY (codelist_id) REFERENCES codelists (id) ON DELETE CASCADE,
					CONSTRAINT fk_codelist_item_parent FOREIGN KEY (parent_id) REFERENCES codelist_item (id) ON DELETE SET NULL
				)
			");
			$this->db->query('CREATE INDEX idx_codelist_item_parent ON codelist_item (parent_id)');
			$this->db->query('CREATE INDEX idx_codelist_item_sort ON codelist_item (codelist_id, sort_order)');
		}
		log_message('info', 'Created codelist_item table');
	}

	private function create_codelist_item_translation_table($is_mysql)
	{
		if ($this->db->table_exists('codelist_item_translation')) {
			log_message('info', 'codelist_item_translation already exists');
			return;
		}
		if ($is_mysql) {
			$this->db->query("
				CREATE TABLE `codelist_item_translation` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`codelist_item_id` INT(11) NOT NULL,
					`lang` VARCHAR(32) NOT NULL,
					`title` VARCHAR(255) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `unq_codelist_item_trans` (`codelist_item_id`, `lang`),
					KEY `idx_codelist_item_trans_lang` (`lang`),
					CONSTRAINT `fk_codelist_item_trans_item` FOREIGN KEY (`codelist_item_id`) REFERENCES `codelist_item` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8
			");
		} else {
			$this->db->query("
				CREATE TABLE codelist_item_translation (
					id INT NOT NULL IDENTITY(1,1),
					codelist_item_id INT NOT NULL,
					lang VARCHAR(32) NOT NULL,
					title VARCHAR(255) NOT NULL,
					PRIMARY KEY (id),
					CONSTRAINT unq_codelist_item_trans UNIQUE (codelist_item_id, lang),
					CONSTRAINT fk_codelist_item_trans_item FOREIGN KEY (codelist_item_id) REFERENCES codelist_item (id) ON DELETE CASCADE
				)
			");
			$this->db->query('CREATE INDEX idx_codelist_item_trans_lang ON codelist_item_translation (lang)');
		}
		log_message('info', 'Created codelist_item_translation table');
	}

	private function create_codelist_group_table($is_mysql)
	{
		if ($this->db->table_exists('codelist_group')) {
			log_message('info', 'codelist_group already exists');
			return;
		}
		if ($is_mysql) {
			$this->db->query("
				CREATE TABLE `codelist_group` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`codelist_id` INT(11) NOT NULL,
					`name` VARCHAR(64) NOT NULL,
					`sort_order` INT(11) NOT NULL DEFAULT 0,
					PRIMARY KEY (`id`),
					UNIQUE KEY `unq_codelist_group_name` (`codelist_id`, `name`),
					KEY `idx_codelist_group_sort` (`codelist_id`, `sort_order`),
					CONSTRAINT `fk_codelist_group_codelist` FOREIGN KEY (`codelist_id`) REFERENCES `codelists` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8
			");
		} else {
			$this->db->query("
				CREATE TABLE codelist_group (
					id INT NOT NULL IDENTITY(1,1),
					codelist_id INT NOT NULL,
					name VARCHAR(64) NOT NULL,
					sort_order INT NOT NULL DEFAULT 0,
					PRIMARY KEY (id),
					CONSTRAINT unq_codelist_group_name UNIQUE (codelist_id, name),
					CONSTRAINT fk_codelist_group_codelist FOREIGN KEY (codelist_id) REFERENCES codelists (id) ON DELETE CASCADE
				)
			");
			$this->db->query('CREATE INDEX idx_codelist_group_sort ON codelist_group (codelist_id, sort_order)');
		}
		log_message('info', 'Created codelist_group table');
	}

	private function create_codelist_group_item_table($is_mysql)
	{
		if ($this->db->table_exists('codelist_group_item')) {
			log_message('info', 'codelist_group_item already exists');
			return;
		}
		if ($is_mysql) {
			$this->db->query("
				CREATE TABLE `codelist_group_item` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`codelist_group_id` INT(11) NOT NULL,
					`codelist_item_id` INT(11) NOT NULL,
					`sort_order` INT(11) NOT NULL DEFAULT 0,
					PRIMARY KEY (`id`),
					UNIQUE KEY `unq_codelist_group_item` (`codelist_group_id`, `codelist_item_id`),
					CONSTRAINT `fk_codelist_grp_item_grp` FOREIGN KEY (`codelist_group_id`) REFERENCES `codelist_group` (`id`) ON DELETE CASCADE,
					CONSTRAINT `fk_codelist_grp_item_item` FOREIGN KEY (`codelist_item_id`) REFERENCES `codelist_item` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8
			");
		} else {
			$this->db->query("
				CREATE TABLE codelist_group_item (
					id INT NOT NULL IDENTITY(1,1),
					codelist_group_id INT NOT NULL,
					codelist_item_id INT NOT NULL,
					sort_order INT NOT NULL DEFAULT 0,
					PRIMARY KEY (id),
					CONSTRAINT unq_codelist_group_item UNIQUE (codelist_group_id, codelist_item_id),
					CONSTRAINT fk_codelist_grp_item_grp FOREIGN KEY (codelist_group_id) REFERENCES codelist_group (id) ON DELETE CASCADE,
					CONSTRAINT fk_codelist_grp_item_item FOREIGN KEY (codelist_item_id) REFERENCES codelist_item (id) ON DELETE CASCADE
				)
			");
		}
		log_message('info', 'Created codelist_group_item table');
	}

	private function create_codelist_group_translation_table($is_mysql)
	{
		if ($this->db->table_exists('codelist_group_translation')) {
			log_message('info', 'codelist_group_translation already exists');
			return;
		}
		if ($is_mysql) {
			$this->db->query("
				CREATE TABLE `codelist_group_translation` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`codelist_group_id` INT(11) NOT NULL,
					`lang` VARCHAR(32) NOT NULL,
					`title` VARCHAR(255) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `unq_codelist_group_trans` (`codelist_group_id`, `lang`),
					KEY `idx_codelist_group_trans_lang` (`lang`),
					CONSTRAINT `fk_codelist_group_trans_grp` FOREIGN KEY (`codelist_group_id`) REFERENCES `codelist_group` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8
			");
		} else {
			$this->db->query("
				CREATE TABLE codelist_group_translation (
					id INT NOT NULL IDENTITY(1,1),
					codelist_group_id INT NOT NULL,
					lang VARCHAR(32) NOT NULL,
					title VARCHAR(255) NOT NULL,
					PRIMARY KEY (id),
					CONSTRAINT unq_codelist_group_trans UNIQUE (codelist_group_id, lang),
					CONSTRAINT fk_codelist_group_trans_grp FOREIGN KEY (codelist_group_id) REFERENCES codelist_group (id) ON DELETE CASCADE
				)
			");
			$this->db->query('CREATE INDEX idx_codelist_group_trans_lang ON codelist_group_translation (lang)');
		}
		log_message('info', 'Created codelist_group_translation table');
	}

	/**
	 * Seed 'dctypes' codelist from existing dctypes/dctype_translations and default groups from config.
	 */
	private function seed_dctypes_codelist()
	{
		if (!$this->db->table_exists('dctypes') || !$this->db->table_exists('dctype_translations')) {
			log_message('info', 'dctypes/dctype_translations not present; skipping codelist seed');
			return;
		}

		$_r = $this->db->get_where('codelists', ['name' => 'dctypes']);
		$codelist = $_r ? $_r->row_array() : null;
		if ($codelist) {
			log_message('info', 'dctypes codelist already seeded');
			return;
		}

		$this->db->insert('codelists', ['name' => 'dctypes', 'description' => 'Resource types (external resources)']);
		$codelist_id = (int) $this->db->insert_id();
		if ($codelist_id <= 0) {
			return;
		}

		$_r = $this->db->order_by('id')->get('dctypes');
		$dctypes = $_r ? $_r->result_array() : [];
		$old_id_to_new_id = [];
		$sort = 0;
		foreach ($dctypes as $row) {
			$this->db->insert('codelist_item', [
				'codelist_id' => $codelist_id,
				'parent_id'   => null,
				'code'        => $row['code'],
				'title'       => isset($row['title']) ? $row['title'] : null,
				'sort_order'  => $sort,
			]);
			$old_id_to_new_id[(int) $row['id']] = (int) $this->db->insert_id();
			$sort += 10;
		}

		$_r = $this->db->get('dctype_translations');
		$trans = $_r ? $_r->result_array() : [];
		foreach ($trans as $row) {
			$new_item_id = isset($old_id_to_new_id[(int) $row['dctype_id']]) ? $old_id_to_new_id[(int) $row['dctype_id']] : null;
			if ($new_item_id) {
				$this->db->insert('codelist_item_translation', [
					'codelist_item_id' => $new_item_id,
					'lang'             => $row['lang'],
					'title'            => $row['title'],
				]);
			}
		}

		$default_groups = [
			'questionnaires' => ['doc/qst'],
			'reports'         => ['doc/rep'],
			'technical'       => ['doc/tec'],
			'reproducible'    => ['repro'],
			'final'           => ['final'],
		];
		$sort_order = 10;
		foreach ($default_groups as $group_name => $codes) {
			$this->db->insert('codelist_group', [
				'codelist_id' => $codelist_id,
				'name'        => $group_name,
				'sort_order'  => $sort_order,
			]);
			$group_id = (int) $this->db->insert_id();
			$sort_order += 10;
			$item_sort = 0;
			foreach ($codes as $code) {
				$_r = $this->db->get_where('codelist_item', ['codelist_id' => $codelist_id, 'code' => $code]);
			$item = $_r ? $_r->row_array() : null;
				if ($item) {
					$this->db->insert('codelist_group_item', [
						'codelist_group_id' => $group_id,
						'codelist_item_id'  => $item['id'],
						'sort_order'         => $item_sort,
					]);
					$item_sort += 10;
				}
			}
		}

		log_message('info', 'Seeded dctypes codelist and default groups');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
