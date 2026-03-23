<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: dctypes code column + dctype_translations table
 *
 * - Adds code column to dctypes (ISO-style code e.g. doc/qst).
 * - Replaces dctypes content with clean titles (no [code] in title) and codes.
 * - Creates dctype_translations (dctype_id, lang, title) for per-language labels.
 * - Seeds dctype_translations with English from dctypes.title (lang = 'en', from iso_languages).
 * - dctypes.title remains as fallback when no translation exists for a language.
 */
class Migration_Dctypes_code_and_translations extends MY_Migration {

	/** Canonical dctypes: title (clean, no code) => code */
	private $dctypes_data = [
		'Document, Administrative' => 'doc/adm',
		'Document, Analytical'      => 'doc/anl',
		'Document, Other'           => 'doc/oth',
		'Document, Questionnaire'  => 'doc/qst',
		'Document, Reference'       => 'doc/ref',
		'Document, Report'          => 'doc/rep',
		'Document, Technical'      => 'doc/tec',
		'Audio'                     => 'aud',
		'Database'                  => 'dat',
		'Map'                       => 'map',
		'Microdata File'            => 'dat/micro',
		'Photo'                     => 'pic',
		'Program'                   => 'prg',
		'Table'                     => 'tbl',
		'Video'                     => 'vid',
		'Web Site'                  => 'web',
		'Data, Geospatial'          => 'dat/geo',
		'Data, Table'               => 'dat/table',
		'Data, Document'            => 'dat/doc',
	];

	public function up()
	{
		log_message('info', 'Migration_Dctypes_code_and_translations::up() called');

		$driver = $this->db->dbdriver;
		$is_mysql = in_array($driver, ['mysql', 'mysqli']);

		// 1. Add code column (nullable first)
		$this->add_code_column($is_mysql);

		// 2. Replace dctypes content with clean title + code
		$this->replace_dctypes_content();

		// 3. Make code NOT NULL and UNIQUE
		$this->constrain_code_column($is_mysql);

		// 4. Create dctype_translations table
		$this->create_dctype_translations_table($is_mysql);

		// 5. Seed dctype_translations with English from dctypes.title (fallback source)
		$this->seed_dctype_translations_en();

		log_message('info', 'Migration_Dctypes_code_and_translations completed successfully');
	}

	private function add_code_column($is_mysql)
	{
		if ($is_mysql) {
			$this->db->query('ALTER TABLE `dctypes` ADD COLUMN `code` VARCHAR(64) NULL DEFAULT NULL AFTER `id`');
		} else {
			$this->db->query('ALTER TABLE dctypes ADD code VARCHAR(64) NULL');
		}
		log_message('info', 'Added dctypes.code column');
	}

	private function replace_dctypes_content()
	{
		$this->db->truncate('dctypes');

		foreach ($this->dctypes_data as $title => $code) {
			$this->db->insert('dctypes', [
				'title' => $title,
				'code'  => $code,
			]);
		}
		log_message('info', 'Replaced dctypes content with clean title + code');
	}

	private function constrain_code_column($is_mysql)
	{
		if ($is_mysql) {
			$this->db->query('ALTER TABLE `dctypes` MODIFY COLUMN `code` VARCHAR(64) NOT NULL');
			$this->db->query('ALTER TABLE `dctypes` ADD UNIQUE KEY `unq_dctypes_code` (`code`)');
		} else {
			$this->db->query('ALTER TABLE dctypes ALTER COLUMN code VARCHAR(64) NOT NULL');
			$this->db->query('CREATE UNIQUE INDEX unq_dctypes_code ON dctypes (code)');
		}
		log_message('info', 'Set dctypes.code NOT NULL and UNIQUE');
	}

	private function create_dctype_translations_table($is_mysql)
	{
		if ($this->db->table_exists('dctype_translations')) {
			log_message('info', 'dctype_translations already exists, skipping create');
			return;
		}

		if ($is_mysql) {
			$this->db->query("
				CREATE TABLE `dctype_translations` (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`dctype_id` INT(11) NOT NULL,
					`lang` VARCHAR(32) NOT NULL,
					`title` VARCHAR(255) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `unq_dctype_lang` (`dctype_id`, `lang`),
					KEY `idx_dctype_translations_lang` (`lang`),
					CONSTRAINT `fk_dctype_translations_dctype` FOREIGN KEY (`dctype_id`) REFERENCES `dctypes` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8
			");
		} else {
			$this->db->query("
				CREATE TABLE dctype_translations (
					id INT NOT NULL IDENTITY(1,1),
					dctype_id INT NOT NULL,
					lang VARCHAR(32) NOT NULL,
					title VARCHAR(255) NOT NULL,
					PRIMARY KEY (id),
					CONSTRAINT unq_dctype_lang UNIQUE (dctype_id, lang),
					CONSTRAINT fk_dctype_translations_dctype FOREIGN KEY (dctype_id) REFERENCES dctypes (id) ON DELETE CASCADE
				)
			");
			$this->db->query('CREATE INDEX idx_dctype_translations_lang ON dctype_translations (lang)');
		}
		log_message('info', 'Created dctype_translations table');
	}

	private function seed_dctype_translations_en()
	{
		$_r = $this->db->select('id, title')->get('dctypes');
		$rows = $_r ? $_r->result_array() : [];
		$inserts = [];
		foreach ($rows as $row) {
			$inserts[] = [
				'dctype_id' => $row['id'],
				'lang'      => 'en',
				'title'     => $row['title'],
			];
		}
		if (!empty($inserts)) {
			$this->db->insert_batch('dctype_translations', $inserts);
		}
		log_message('info', 'Seeded dctype_translations with lang=en from dctypes.title');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
