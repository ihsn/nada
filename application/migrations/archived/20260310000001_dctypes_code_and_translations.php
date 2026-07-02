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
		if ($this->db->field_exists('code', 'dctypes')) {
			log_message('info', 'dctypes.code already exists, skipping add column');
			return;
		}

		if ($is_mysql) {
			$this->db->query('ALTER TABLE `dctypes` ADD COLUMN `code` VARCHAR(64) NULL DEFAULT NULL AFTER `id`');
		} else {
			$this->db->query('ALTER TABLE dctypes ADD code VARCHAR(64) NULL');
		}
		log_message('info', 'Added dctypes.code column');
	}

	private function replace_dctypes_content()
	{
		$this->upgrade_legacy_dctype_rows();

		$inserted = 0;
		$updated = 0;

		foreach ($this->dctypes_data as $title => $code) {
			$existing = $this->db->where('code', $code)->get('dctypes')->row_array();
			if ($existing) {
				if ($existing['title'] !== $title) {
					$this->db->where('id', (int)$existing['id'])->update('dctypes', array('title' => $title));
					$updated++;
				}
				continue;
			}

			$by_title = $this->db->where('title', $title)->get('dctypes')->row_array();
			if ($by_title) {
				$this->db->where('id', (int)$by_title['id'])->update('dctypes', array('code' => $code));
				$updated++;
				continue;
			}

			$this->db->insert('dctypes', array(
				'title' => $title,
				'code'  => $code,
			));
			$inserted++;
		}

		log_message('info', 'Ensured canonical dctypes content (inserted=' . $inserted . ', updated=' . $updated . ')');
	}

	/**
	 * Convert legacy titles like "Document, Questionnaire [doc/qst]" in place.
	 */
	private function upgrade_legacy_dctype_rows()
	{
		$rows = $this->db->get('dctypes')->result_array();
		$upgraded = 0;

		foreach ($rows as $row) {
			$title = isset($row['title']) ? (string)$row['title'] : '';
			$code = isset($row['code']) ? trim((string)$row['code']) : '';

			if ($code !== '' || !preg_match('/\[([^\]]+)\]\s*$/', $title, $matches)) {
				continue;
			}

			$extracted_code = strtolower(trim($matches[1]));
			$clean_title = trim(preg_replace('/\s*\[[^\]]+\]\s*$/', '', $title));
			if ($extracted_code === '' || $clean_title === '') {
				continue;
			}

			$this->db->where('id', (int)$row['id'])->update('dctypes', array(
				'title' => $clean_title,
				'code'  => $extracted_code,
			));
			$upgraded++;
		}

		if ($upgraded > 0) {
			log_message('info', 'Upgraded legacy dctype rows: ' . $upgraded);
		}
	}

	private function constrain_code_column($is_mysql)
	{
		if ($is_mysql) {
			if ($this->mysql_column_allows_null('dctypes', 'code')) {
				$this->db->query('ALTER TABLE `dctypes` MODIFY COLUMN `code` VARCHAR(64) NOT NULL');
				log_message('info', 'Set dctypes.code NOT NULL (MySQL)');
			}

			if (!$this->mysql_index_exists('dctypes', 'unq_dctypes_code')) {
				$this->db->query('ALTER TABLE `dctypes` ADD UNIQUE KEY `unq_dctypes_code` (`code`)');
				log_message('info', 'Added unq_dctypes_code (MySQL)');
			}
		} else {
			if ($this->sqlsrv_column_allows_null('dctypes', 'code')) {
				$this->db->query('ALTER TABLE dctypes ALTER COLUMN code VARCHAR(64) NOT NULL');
				log_message('info', 'Set dctypes.code NOT NULL (SQLSRV)');
			}

			if (!$this->sqlsrv_index_exists('dctypes', 'unq_dctypes_code')) {
				$this->db->query('CREATE UNIQUE INDEX unq_dctypes_code ON dctypes (code)');
				log_message('info', 'Added unq_dctypes_code (SQLSRV)');
			}
		}
	}

	private function mysql_column_allows_null($table, $column)
	{
		$_r = $this->db->query(
			'SELECT is_nullable FROM information_schema.columns
			 WHERE table_schema = DATABASE()
			   AND table_name = ?
			   AND column_name = ?',
			array($table, $column)
		);
		$row = $_r ? $_r->row_array() : null;

		return $row && strtoupper((string)$row['is_nullable']) === 'YES';
	}

	private function mysql_index_exists($table, $index_name)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM information_schema.statistics
			 WHERE table_schema = DATABASE()
			   AND table_name = ?
			   AND index_name = ?',
			array($table, $index_name)
		);

		return $_r && $_r->row_array();
	}

	private function sqlsrv_column_allows_null($table, $column)
	{
		$_r = $this->db->query(
			'SELECT c.is_nullable
			 FROM sys.columns c
			 INNER JOIN sys.tables t ON c.object_id = t.object_id
			 WHERE t.name = ? AND c.name = ?',
			array($table, $column)
		);
		$row = $_r ? $_r->row_array() : null;

		return $row && (int)$row['is_nullable'] === 1;
	}

	private function sqlsrv_index_exists($table, $index_name)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)',
			array($index_name, $table)
		);

		return $_r && $_r->row_array();
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
		if (!$this->db->table_exists('dctype_translations')) {
			return;
		}

		$_r = $this->db->select('id, title')->get('dctypes');
		$rows = $_r ? $_r->result_array() : [];
		$inserted = 0;

		foreach ($rows as $row) {
			$exists = $this->db
				->where('dctype_id', (int)$row['id'])
				->where('lang', 'en')
				->get('dctype_translations')
				->row_array();

			if ($exists) {
				continue;
			}

			$this->db->insert('dctype_translations', array(
				'dctype_id' => (int)$row['id'],
				'lang'      => 'en',
				'title'     => $row['title'],
			));
			$inserted++;
		}

		log_message('info', 'Seeded dctype_translations with lang=en (inserted=' . $inserted . ')');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
