<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');
require_once(APPPATH . 'libraries/Citation_csl.php');

/**
 * Citations: CSL-JSON document + projected columns (CITATIONS_MIGRATION_PLAN.md, Phase 1).
 *
 *  0. snapshot the pre-CSL tables for the admin to drop later:
 *       citations_legacy_backup         full copy of citations (before any ALTER)
 *       citation_authors_legacy_backup  full copy of citation_authors (live table kept for backfill)
 *  1. add metadata (CSL-JSON), container_title, authors_text, search_text, url_status_time
 *  2. widen title/url/flag; SQL Server: nvarchar for the columns that stay
 *  3. backfill metadata + projected columns from the legacy columns and citation_authors (batches of 500)
 *  4. survey_citations: remove orphans, NOT NULL, foreign keys (InnoDB / SQL Server)
 *  5. metadata NOT NULL (+ ISJSON check on SQL Server), published default 0
 *  6. full-text indexes on the projected columns; indexes on doi and changed
 *  7. citation flags codelist (NADA:citation_flags)
 *
 * The legacy columns (subtitle, authors, volume, ...) and citation_authors stay on the live tables until
 * migration 20261112120001_citations_drop_legacy removes them. The *_legacy_backup copies are left for
 * the site admin to drop after checking the conversion.
 * Every step checks first and can be re-run.
 */
class Migration_Citations_csl extends MY_Migration {

	const BATCH_SIZE = 500;

	/** SQL Server columns converted to nvarchar: column => [length (0 = max), NOT NULL] */
	private $sqlsrv_text_columns = array(
		'uuid'       => array(50, TRUE),
		'title'      => array(1000, TRUE),
		'ctype'      => array(45, TRUE),
		'flag'       => array(64, FALSE),
		'notes'      => array(0, FALSE),
		'owner'      => array(255, FALSE),
		'lang'       => array(45, FALSE),
		'url_status' => array(50, FALSE),
		'url'        => array(2048, FALSE),
		'doi'        => array(255, FALSE),
		'attachment' => array(300, FALSE),
	);

	/** Citation flags seeded into the codelist: code => title (codes are the values already stored) */
	private $flags = array(
		'ds_unclear'     => 'Unclear related dataset',
		'incomplete'     => 'Incomplete',
		'tobe_checked'   => 'To be checked',
		'duplicate'      => 'Duplicate',
		'back_to_editor' => 'Back to editor',
	);

	public function up()
	{
		if (!$this->table_exists('citations')) {
			$this->emit("citations table not found; nothing to do\n");
			return;
		}

		$fresh = $this->sqlsrv_has_column('citations', 'metadata') && !$this->sqlsrv_has_column('citations', 'subtitle');
		if ($fresh) {
			$this->emit("citations already uses the CSL layout (fresh install); seeding flags only\n");
		} else {
			$this->backup_legacy_tables();
			$this->add_columns();
			$this->alter_columns();
			$this->backfill_metadata();
			$this->survey_citations_fks();
			$this->finalize_columns();
			$this->rebuild_indexes();
			$this->emit(
				"\nPre-CSL copies are in citations_legacy_backup and citation_authors_legacy_backup."
				. " After migration 20261112120001 and a check of your citations, drop them:\n"
				. "  DROP TABLE citations_legacy_backup;\n"
				. "  DROP TABLE citation_authors_legacy_backup;\n"
			);
		}
		$this->seed_flag_codelist();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function is_mysql()
	{
		return in_array($this->db->dbdriver, array('mysql', 'mysqli'), TRUE);
	}

	private function is_sqlsrv()
	{
		return $this->db->dbdriver === 'sqlsrv';
	}

	// ---------------------------------------------------------------------------------------------
	// 0. backup (full table copies; admin drops them later)
	// ---------------------------------------------------------------------------------------------

	/**
	 * Freeze the pre-CSL shape before any ALTER/backfill. Copies only — live tables stay for conversion.
	 * Skipped when the backup tables already exist (re-run) or when this is not an upgrade path.
	 */
	private function backup_legacy_tables()
	{
		$this->emit("0. backup pre-CSL tables\n");
		$this->db->data_cache = array();

		if (!$this->table_exists('citations_legacy_backup')) {
			$this->assert_db_query($this->db->query($this->backup_copy_sql('citations', 'citations_legacy_backup')), 'create citations_legacy_backup');
			$this->forget_table_cache();

			$copied = (int) $this->db->query('SELECT COUNT(*) AS n FROM citations_legacy_backup')->row()->n;
			$total = (int) $this->db->query('SELECT COUNT(*) AS n FROM citations')->row()->n;
			if ($copied !== $total) {
				throw new Exception(
					"citations_legacy_backup has {$copied} rows, expected {$total}; conversion not started."
				);
			}
			$this->emit("   + citations_legacy_backup ({$copied} rows, full pre-CSL copy)\n");
		} else {
			$this->emit("   citations_legacy_backup already exists; leaving it\n");
		}

		if ($this->table_exists('citation_authors')) {
			if (!$this->table_exists('citation_authors_legacy_backup')) {
				$this->assert_db_query(
					$this->db->query($this->backup_copy_sql('citation_authors', 'citation_authors_legacy_backup')),
					'create citation_authors_legacy_backup'
				);
				$this->forget_table_cache();

				$copied = (int) $this->db->query('SELECT COUNT(*) AS n FROM citation_authors_legacy_backup')->row()->n;
				$total = (int) $this->db->query('SELECT COUNT(*) AS n FROM citation_authors')->row()->n;
				if ($copied !== $total) {
					throw new Exception(
						"citation_authors_legacy_backup has {$copied} rows, expected {$total}; conversion not started."
					);
				}
				$this->emit("   + citation_authors_legacy_backup ({$copied} rows; live citation_authors kept for backfill)\n");
			} else {
				$this->emit("   citation_authors_legacy_backup already exists; leaving it\n");
			}
		} else {
			$this->emit("   citation_authors not present; no authors backup\n");
		}
	}

	/**
	 * Full-table snapshot SQL for MySQL/MariaDB or SQL Server.
	 */
	private function backup_copy_sql($from_table, $to_table)
	{
		if ($this->is_sqlsrv()) {
			return 'SELECT * INTO ' . $to_table . ' FROM ' . $from_table;
		}
		if ($this->is_mysql()) {
			return 'CREATE TABLE `' . $to_table . '` AS SELECT * FROM `' . $from_table . '`';
		}
		throw new Exception('Unsupported database driver: ' . $this->db->dbdriver);
	}

	// ---------------------------------------------------------------------------------------------
	// 1. columns
	// ---------------------------------------------------------------------------------------------

	private function add_columns()
	{
		$this->emit("1. add columns\n");
		$columns = array(
			'metadata'        => array('mysql' => 'JSON NULL',     'sqlsrv' => 'NVARCHAR(MAX) NULL'),
			'container_title' => array('mysql' => 'VARCHAR(500) NULL', 'sqlsrv' => 'NVARCHAR(500) NULL'),
			'authors_text'    => array('mysql' => 'TEXT NULL',     'sqlsrv' => 'NVARCHAR(MAX) NULL'),
			'search_text'     => array('mysql' => 'MEDIUMTEXT NULL', 'sqlsrv' => 'NVARCHAR(MAX) NULL'),
			'url_status_time' => array('mysql' => 'INT NULL',      'sqlsrv' => 'INT NULL'),
		);
		foreach ($columns as $column => $types) {
			if ($this->sqlsrv_has_column('citations', $column)) {
				continue;
			}
			if ($this->is_mysql()) {
				$sql = 'ALTER TABLE `citations` ADD COLUMN `' . $column . '` ' . $types['mysql'];
			} elseif ($this->is_sqlsrv()) {
				$sql = 'ALTER TABLE citations ADD [' . $column . '] ' . $types['sqlsrv'];
			} else {
				throw new Exception('Unsupported database driver: ' . $this->db->dbdriver);
			}
			$this->assert_db_query($this->db->query($sql), 'add citations.' . $column);
			$this->emit("   + citations.{$column}\n");
		}
		$this->forget_table_cache();
	}

	private function alter_columns()
	{
		$this->emit("2. widen columns\n");
		if ($this->is_mysql()) {
			$this->mysql_modify_if('title', 'varchar', 1000, 'VARCHAR(1000) NOT NULL');
			$this->mysql_modify_if('url', 'varchar', 2048, 'VARCHAR(2048) NULL');
			$this->mysql_modify_if('flag', 'varchar', 64, 'VARCHAR(64) NULL');
			return;
		}

		if (!$this->is_sqlsrv()) {
			return;
		}

		// ALTER COLUMN fails on columns used by the full-text index or by an index: drop those first
		$this->sqlsrv_drop_fulltext();

		foreach ($this->sqlsrv_text_columns as $column => $spec) {
			list($length, $not_null) = $spec;
			if (!$this->sqlsrv_has_column('citations', $column)) {
				continue;
			}
			$row = $this->db->query(
				"SELECT DATA_TYPE AS t, CHARACTER_MAXIMUM_LENGTH AS len FROM INFORMATION_SCHEMA.COLUMNS
				 WHERE TABLE_NAME = 'citations' AND COLUMN_NAME = ?",
				array($column)
			)->row_array();
			$current = (int) $row['len'];
			$wide_enough = $current === -1 || ($length > 0 && $current >= $length);
			if ($row['t'] === 'nvarchar' && $wide_enough) {
				continue;
			}
			$type = $length === 0 ? 'NVARCHAR(MAX)' : 'NVARCHAR(' . max($length, $current) . ')';

			$this->sqlsrv_alter_column($column, $type . ($not_null ? ' NOT NULL' : ' NULL'));
			$this->emit("   ~ citations.{$column} {$type}\n");
		}
	}

	/**
	 * ALTER COLUMN on SQL Server, stepping around what blocks it: indexes on the column are dropped and recreated,
	 * and the default constraint is dropped and (unless $restore_default is FALSE) put back.
	 *
	 * @param string $type_sql type with nullability, e.g. "NVARCHAR(255) NULL" or "TINYINT NOT NULL"
	 */
	private function sqlsrv_alter_column($column, $type_sql, $restore_default = TRUE)
	{
		$indexes = $this->sqlsrv_indexes_on_column($column);
		foreach ($indexes as $index) {
			$this->assert_db_query($this->db->query('DROP INDEX [' . $index['name'] . '] ON citations'), 'drop index ' . $index['name']);
		}
		$default = $this->db->query(
			"SELECT dc.name, dc.definition FROM sys.default_constraints dc
			 JOIN sys.columns c ON c.object_id = dc.parent_object_id AND c.column_id = dc.parent_column_id
			 WHERE dc.parent_object_id = OBJECT_ID('citations') AND c.name = ?",
			array($column)
		)->row_array();
		if ($default) {
			$this->assert_db_query($this->db->query('ALTER TABLE citations DROP CONSTRAINT [' . $default['name'] . ']'), 'drop default of citations.' . $column);
		}

		$this->assert_db_query(
			$this->db->query('ALTER TABLE citations ALTER COLUMN [' . $column . '] ' . $type_sql),
			'alter citations.' . $column
		);

		if ($restore_default && $default && strtoupper(trim($default['definition'], '() ')) !== 'NULL') {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE citations ADD CONSTRAINT [' . $default['name'] . '] DEFAULT ' . $default['definition'] . ' FOR [' . $column . ']'),
				'restore default of citations.' . $column
			);
		}
		foreach ($indexes as $index) {
			$this->assert_db_query($this->db->query(
				'CREATE ' . ($index['is_unique'] ? 'UNIQUE ' : '') . 'NONCLUSTERED INDEX [' . $index['name'] . '] ON citations ('
				. implode(', ', array_map(function ($c) { return '[' . $c . ']'; }, $index['columns'])) . ')'
			), 'recreate index ' . $index['name']);
		}
	}

	private function mysql_modify_if($column, $type, $length, $definition)
	{
		$row = $this->db->query(
			'SELECT DATA_TYPE AS t, CHARACTER_MAXIMUM_LENGTH AS len FROM information_schema.COLUMNS
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
			array('citations', $column)
		)->row_array();
		if (!$row || ($row['t'] === $type && (int) $row['len'] >= $length)) {
			return;
		}
		$this->assert_db_query($this->db->query('ALTER TABLE `citations` MODIFY `' . $column . '` ' . $definition), 'widen citations.' . $column);
		$this->emit("   ~ citations.{$column} {$definition}\n");
	}

	/**
	 * Non-PK indexes of citations that include the column: [{name, is_unique, columns[]}]
	 */
	private function sqlsrv_indexes_on_column($column)
	{
		$rows = $this->db->query(
			"SELECT i.name, i.is_unique, c.name AS col, ic.key_ordinal
			 FROM sys.indexes i
			 JOIN sys.index_columns ic ON ic.object_id = i.object_id AND ic.index_id = i.index_id
			 JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
			 WHERE i.object_id = OBJECT_ID('citations') AND i.is_primary_key = 0 AND i.name IS NOT NULL
			   AND i.name IN (
			     SELECT i2.name FROM sys.indexes i2
			     JOIN sys.index_columns ic2 ON ic2.object_id = i2.object_id AND ic2.index_id = i2.index_id
			     JOIN sys.columns c2 ON c2.object_id = ic2.object_id AND c2.column_id = ic2.column_id
			     WHERE i2.object_id = OBJECT_ID('citations') AND c2.name = ?)
			 ORDER BY i.name, ic.key_ordinal",
			array($column)
		)->result_array();

		$out = array();
		foreach ($rows as $r) {
			if (!isset($out[$r['name']])) {
				$out[$r['name']] = array('name' => $r['name'], 'is_unique' => (int) $r['is_unique'] === 1, 'columns' => array());
			}
			$out[$r['name']]['columns'][] = $r['col'];
		}
		return array_values($out);
	}

	// ---------------------------------------------------------------------------------------------
	// 3. backfill
	// ---------------------------------------------------------------------------------------------

	private function backfill_metadata()
	{
		$this->emit("3. backfill metadata\n");
		$csl = new Citation_csl();
		$has_authors_table = $this->table_exists('citation_authors');
		$done = 0;
		$failed = 0;

		while (true) {
			if ($this->is_sqlsrv()) {
				$rows = $this->db->query('SELECT TOP ' . self::BATCH_SIZE . ' * FROM citations WHERE metadata IS NULL ORDER BY id')->result_array();
			} else {
				$rows = $this->db->query('SELECT * FROM citations WHERE metadata IS NULL ORDER BY id LIMIT ' . self::BATCH_SIZE)->result_array();
			}
			if (empty($rows)) {
				break;
			}

			$authors = array();
			if ($has_authors_table) {
				$this->db->where_in('cid', array_column($rows, 'id'));
				$this->db->order_by('cid', 'ASC');
				$this->db->order_by('id', 'ASC');
				foreach ($this->db->get('citation_authors')->result_array() as $a) {
					$authors[(int) $a['cid']][] = $a;
				}
			}

			$this->db->trans_start();
			foreach ($rows as $row) {
				$id = (int) $row['id'];
				try {
					// citation_authors is what the admin showed; without rows there, names come from the JSON text columns
					$doc = $csl->from_legacy_row($row, isset($authors[$id]) ? $authors[$id] : NULL);
					if (trim((string) $doc['title']) === '') {
						$doc['title'] = '(untitled)';
					}
				} catch (Throwable $e) {
					$failed++;
					$doc = array(
						'type'   => 'report',
						'title'  => (string) $row['title'] !== '' ? (string) $row['title'] : '(untitled)',
						'custom' => array('nada' => array('legacy_ctype' => (string) $row['ctype'], 'backfill_error' => $e->getMessage())),
					);
				}

				$update = $csl->project_columns($doc);
				$update['metadata'] = json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				$this->db->where('id', $id);
				$this->db->update('citations', $update);
			}
			$this->db->trans_complete();
			if ($this->db->trans_status() === FALSE) {
				$error = $this->db->error();
				throw new Exception('Backfill failed near citation ' . $rows[0]['id'] . ': ' . $error['message']);
			}

			$done += count($rows);
			$this->emit("   {$done} citations\n");
			$this->emit_flush();
		}

		$this->emit("   backfilled {$done}" . ($failed ? ", {$failed} with backfill_error" : '') . "\n");
	}

	// ---------------------------------------------------------------------------------------------
	// 4. survey_citations
	// ---------------------------------------------------------------------------------------------

	private function survey_citations_fks()
	{
		$this->emit("4. survey_citations foreign keys\n");
		if (!$this->table_exists('survey_citations')) {
			return;
		}

		$removed = $this->db->query(
			'DELETE FROM survey_citations WHERE sid IS NULL OR citationid IS NULL
			 OR sid NOT IN (SELECT id FROM surveys) OR citationid NOT IN (SELECT id FROM citations)'
		);
		$this->assert_db_query($removed, 'remove orphan survey_citations');
		$this->emit('   removed ' . (int) $this->db->affected_rows() . " orphan links\n");

		if ($this->table_exists('citation_authors')) {
			$this->assert_db_query(
				$this->db->query('DELETE FROM citation_authors WHERE cid IS NULL OR cid NOT IN (SELECT id FROM citations)'),
				'remove orphan citation_authors'
			);
			$this->emit('   removed ' . (int) $this->db->affected_rows() . " orphan author rows\n");
		}

		if ($this->is_mysql()) {
			$engines = $this->db->query(
				"SELECT TABLE_NAME AS t, ENGINE AS e FROM information_schema.TABLES
				 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('survey_citations','surveys','citations')"
			)->result_array();
			foreach ($engines as $row) {
				if (strtolower((string) $row['e']) !== 'innodb') {
					$this->emit("   skipped: {$row['t']} uses {$row['e']} (foreign keys need InnoDB)\n");
					return;
				}
			}
			$this->assert_db_query(
				$this->db->query('ALTER TABLE `survey_citations` MODIFY `sid` INT NOT NULL, MODIFY `citationid` INT NOT NULL'),
				'survey_citations NOT NULL'
			);
			foreach (array('fk_survey_citations_sid' => array('sid', 'surveys'), 'fk_survey_citations_cid' => array('citationid', 'citations')) as $name => $fk) {
				$exists = $this->db->query(
					"SELECT 1 AS present FROM information_schema.TABLE_CONSTRAINTS
					 WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'survey_citations' AND CONSTRAINT_NAME = ?",
					array($name)
				)->num_rows() > 0;
				if ($exists) {
					continue;
				}
				$this->assert_db_query($this->db->query(
					'ALTER TABLE `survey_citations` ADD CONSTRAINT `' . $name . '` FOREIGN KEY (`' . $fk[0] . '`) REFERENCES `' . $fk[1] . '` (`id`) ON DELETE CASCADE'
				), $name);
				$this->emit("   + {$name}\n");
			}
			return;
		}

		if ($this->is_sqlsrv()) {
			foreach (array('sid', 'citationid') as $column) {
				$nullable = $this->db->query(
					"SELECT is_nullable FROM sys.columns WHERE object_id = OBJECT_ID('survey_citations') AND name = ?",
					array($column)
				)->row_array();
				if ($nullable && (int) $nullable['is_nullable'] === 1) {
					// the unique index IX_survey_cit covers both columns
					$indexes = $this->db->query(
						"SELECT DISTINCT i.name FROM sys.indexes i
						 JOIN sys.index_columns ic ON ic.object_id = i.object_id AND ic.index_id = i.index_id
						 JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
						 WHERE i.object_id = OBJECT_ID('survey_citations') AND i.is_primary_key = 0 AND c.name = ?",
						array($column)
					)->result_array();
					$definitions = array();
					foreach ($indexes as $index) {
						$definitions[] = $this->sqlsrv_index_definition('survey_citations', $index['name']);
						$this->assert_db_query($this->db->query('DROP INDEX [' . $index['name'] . '] ON survey_citations'), 'drop index ' . $index['name']);
					}
					$this->assert_db_query($this->db->query('ALTER TABLE survey_citations ALTER COLUMN [' . $column . '] INT NOT NULL'), 'survey_citations.' . $column . ' NOT NULL');
					foreach ($definitions as $sql) {
						$this->assert_db_query($this->db->query($sql), 'recreate index');
					}
				}
			}
			foreach (array('fk_survey_citations_sid' => array('sid', 'surveys'), 'fk_survey_citations_cid' => array('citationid', 'citations')) as $name => $fk) {
				if ($this->sqlsrv_constraint_exists($name)) {
					continue;
				}
				$this->assert_db_query($this->db->query(
					'ALTER TABLE survey_citations ADD CONSTRAINT [' . $name . '] FOREIGN KEY ([' . $fk[0] . ']) REFERENCES [' . $fk[1] . '] ([id]) ON DELETE CASCADE'
				), $name);
				$this->emit("   + {$name}\n");
			}
		}
	}

	private function sqlsrv_index_definition($table, $index_name)
	{
		$rows = $this->db->query(
			"SELECT i.is_unique, c.name AS col, ic.is_descending_key
			 FROM sys.indexes i
			 JOIN sys.index_columns ic ON ic.object_id = i.object_id AND ic.index_id = i.index_id
			 JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
			 WHERE i.object_id = OBJECT_ID(" . $this->db->escape($table) . ") AND i.name = ? ORDER BY ic.key_ordinal",
			array($index_name)
		)->result_array();
		$cols = array();
		foreach ($rows as $r) {
			$cols[] = '[' . $r['col'] . ']' . ((int) $r['is_descending_key'] === 1 ? ' DESC' : ' ASC');
		}
		$unique = !empty($rows) && (int) $rows[0]['is_unique'] === 1;
		return 'CREATE ' . ($unique ? 'UNIQUE ' : '') . 'NONCLUSTERED INDEX [' . $index_name . '] ON [' . $table . '] (' . implode(', ', $cols) . ')';
	}

	// ---------------------------------------------------------------------------------------------
	// 5. final column constraints
	// ---------------------------------------------------------------------------------------------

	private function finalize_columns()
	{
		$this->emit("5. finalize columns\n");
		$missing = (int) $this->db->query('SELECT COUNT(*) AS n FROM citations WHERE metadata IS NULL')->row()->n;
		if ($missing > 0) {
			throw new Exception("{$missing} citations still have no metadata");
		}

		$this->assert_db_query($this->db->query('UPDATE citations SET published = 0 WHERE published IS NULL'), 'published NULL -> 0');

		if ($this->is_mysql()) {
			$this->assert_db_query($this->db->query('ALTER TABLE `citations` MODIFY `metadata` JSON NOT NULL'), 'metadata NOT NULL');
			$this->assert_db_query($this->db->query('ALTER TABLE `citations` MODIFY `published` TINYINT NOT NULL DEFAULT 0'), 'published default 0');
			return;
		}

		if ($this->is_sqlsrv()) {
			$this->assert_db_query($this->db->query('ALTER TABLE citations ALTER COLUMN [metadata] NVARCHAR(MAX) NOT NULL'), 'metadata NOT NULL');
			if (!$this->sqlsrv_constraint_exists('ck_citations_metadata_isjson')) {
				$this->assert_db_query(
					$this->db->query('ALTER TABLE citations ADD CONSTRAINT ck_citations_metadata_isjson CHECK (ISJSON(metadata) = 1)'),
					'ck_citations_metadata_isjson'
				);
			}
			// NOT NULL DEFAULT 0, as in install/schema.sqlsrv.sql (the column has an index and, from older installs, a default)
			$this->sqlsrv_alter_column('published', 'TINYINT NOT NULL', FALSE);
			if (!$this->sqlsrv_constraint_exists('DF_citations_published')) {
				$this->assert_db_query($this->db->query('ALTER TABLE citations ADD CONSTRAINT DF_citations_published DEFAULT 0 FOR [published]'), 'published default 0');
			}
		}
	}

	// ---------------------------------------------------------------------------------------------
	// 6. indexes
	// ---------------------------------------------------------------------------------------------

	private function rebuild_indexes()
	{
		$this->emit("6. indexes\n");

		foreach (array('idx_citations_doi' => 'doi', 'idx_citations_changed' => 'changed') as $name => $column) {
			if ($this->index_exists('citations', $name)) {
				continue;
			}
			$sql = $this->is_mysql()
				? 'ALTER TABLE `citations` ADD INDEX `' . $name . '` (`' . $column . '`' . ($column === 'doi' ? '(191)' : '') . ')'
				: 'CREATE NONCLUSTERED INDEX [' . $name . '] ON citations ([' . $column . '])';
			$this->assert_db_query($this->db->query($sql), $name);
			$this->emit("   + {$name}\n");
		}

		if ($this->is_mysql()) {
			foreach (array('ft_citations', 'ft_cit2') as $old) {
				if ($this->index_exists('citations', $old)) {
					$this->assert_db_query($this->db->query('ALTER TABLE `citations` DROP INDEX `' . $old . '`'), 'drop ' . $old);
					$this->emit("   - {$old}\n");
				}
			}
			$fulltext = array(
				'ft_cit_core'   => '`title`, `container_title`, `authors_text`',
				'ft_cit_search' => '`search_text`',
			);
			foreach ($fulltext as $name => $columns) {
				if ($this->index_exists('citations', $name)) {
					continue;
				}
				$this->assert_db_query($this->db->query('ALTER TABLE `citations` ADD FULLTEXT INDEX `' . $name . '` (' . $columns . ')'), $name);
				$this->emit("   + {$name}\n");
			}
			return;
		}

		if ($this->is_sqlsrv()) {
			$this->sqlsrv_drop_fulltext();
			$row = $this->db->query("SELECT FULLTEXTSERVICEPROPERTY('IsFullTextInstalled') AS installed")->row_array();
			if (empty($row['installed'])) {
				$this->emit("   full-text index skipped: Full-Text Search is not installed on this SQL Server\n");
				return;
			}
			$catalogs = $this->db->query('SELECT 1 AS present FROM sys.fulltext_catalogs');
			if (!$catalogs || $catalogs->num_rows() === 0) {
				$this->assert_db_query($this->db->query('CREATE FULLTEXT CATALOG ft AS DEFAULT'), 'CREATE FULLTEXT CATALOG ft');
			}
			if (!$this->index_exists('citations', 'pk_idx_citations')) {
				$this->assert_db_query($this->db->query('CREATE UNIQUE INDEX pk_idx_citations ON dbo.citations(id)'), 'pk_idx_citations');
			}
			// one full-text index per table on SQL Server; public searches list their columns and leave out notes
			$this->assert_db_query($this->db->query(
				'CREATE FULLTEXT INDEX ON citations (title, container_title, authors_text, search_text, notes) KEY INDEX pk_idx_citations'
			), 'citations full-text index');
			$this->emit("   + citations full-text index\n");
		}
	}

	private function sqlsrv_drop_fulltext()
	{
		if (!$this->is_sqlsrv()) {
			return;
		}
		$present = $this->db->query("SELECT 1 AS present FROM sys.fulltext_indexes WHERE object_id = OBJECT_ID('citations')");
		if ($present && $present->num_rows() > 0) {
			$this->assert_db_query($this->db->query('DROP FULLTEXT INDEX ON citations'), 'drop citations full-text index');
		}
	}

	// ---------------------------------------------------------------------------------------------
	// 7. flags codelist
	// ---------------------------------------------------------------------------------------------

	private function seed_flag_codelist()
	{
		$this->emit("7. citation flags codelist\n");
		if (!$this->table_exists('codelists') || !$this->table_exists('codelist_item')) {
			$this->emit("   skipped: codelists tables not found\n");
			return;
		}

		$this->load->model('Codelist_model');
		$this->load->model('Codelist_item_model');

		$codelist = $this->Codelist_model->get_codelist_by_name('citation_flags');
		if (!$codelist) {
			$id = $this->Codelist_model->create_codelist(array(
				'name'        => 'citation_flags',
				'agency'      => 'NADA',
				'version'     => '1.0',
				'idno'        => 'NADA_citation_flags_1.0',
				'description' => 'Editorial flags for citations (admin/citations). Codes are stored in citations.flag.',
				'status'      => Codelist_model::STATUS_DRAFT,
			));
			$codelist = $this->Codelist_model->get_codelist_by_id($id);
			$this->emit("   + codelist NADA_citation_flags_1.0\n");
		}
		$codelist_id = (int) $codelist['id'];

		$existing = array();
		foreach ($this->Codelist_item_model->get_items_by_codelist($codelist_id, false) as $item) {
			$existing[$item['code']] = true;
		}

		$items = $this->flags;
		// every other flag value in use, so no citation points at an unknown code
		foreach ($this->db->query("SELECT DISTINCT flag FROM citations WHERE flag IS NOT NULL AND flag <> ''")->result_array() as $row) {
			$code = trim((string) $row['flag']);
			if ($code === '' || isset($items[$code])) {
				continue;
			}
			if (strlen($code) > 64) {
				$this->emit("   flag value too long for a code, skipped: {$code}\n");
				continue;
			}
			$items[$code] = $code;
		}

		$sort = 0;
		foreach ($items as $code => $title) {
			$sort += 10;
			if (isset($existing[$code])) {
				continue;
			}
			$this->Codelist_item_model->create_item($codelist_id, array('code' => $code, 'title' => $title, 'sort_order' => $sort));
			$this->emit("   + {$code}\n");
		}
	}
}
