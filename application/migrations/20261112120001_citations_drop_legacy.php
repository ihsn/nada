<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Citations: remove the pre-CSL columns (CITATIONS_MIGRATION_PLAN.md, Phase 5).
 *
 * Since migration 20261111120001_citations_csl every citation is a CSL-JSON document in citations.metadata.
 * That migration also creates full-table copies:
 *   citations_legacy_backup
 *   citation_authors_legacy_backup
 * for the site admin to drop after checking the conversion.
 *
 * THIS CANNOT BE UNDONE, so before anything is dropped from the live tables:
 *  - it stops if a citation has no document, or if the earlier backfill had to fall back for it, and
 *  - if the Phase 1 backup tables are missing (older Phase 1 runs), it creates them here as a safety net
 *    (copy of remaining legacy columns + copy/rename of citation_authors), then
 *  - drops the live legacy columns and the live citation_authors table.
 *
 * Back up the database before running migrations anyway. Every step checks first and can be re-run.
 */
class Migration_Citations_drop_legacy extends MY_Migration {

	/** Columns of citations that only held data now in the document (pub_year, doi, url, lang stay: they are copied from it) */
	private $legacy_columns = array(
		'subtitle', 'alt_title', 'authors', 'editors', 'translators', 'volume', 'issue', 'idnumber', 'edition',
		'place_publication', 'place_state', 'publisher', 'publication_medium', 'page_from', 'page_to', 'data_accessed',
		'organization', 'pub_day', 'pub_month', 'abstract', 'keywords', 'country',
	);

	public function up()
	{
		if (!$this->table_exists('citations')) {
			$this->emit("citations table not found; nothing to do\n");
			return;
		}

		// CI caches the column and table lists; an earlier migration in this run may have changed them
		$this->db->data_cache = array();

		$present = array();
		foreach ($this->legacy_columns as $column) {
			if ($this->has_column('citations', $column)) {
				$present[] = $column;
			}
		}
		$has_authors_table = $this->table_exists('citation_authors');

		if (empty($present) && !$has_authors_table) {
			$this->emit("citations already has no legacy columns (fresh install or already migrated)\n");
			if ($this->table_exists('citations_legacy_backup') || $this->table_exists('citation_authors_legacy_backup')) {
				$this->emit(
					"Backup tables are still present. When you have checked the citations, drop them:\n"
					. "  DROP TABLE citations_legacy_backup;\n"
					. "  DROP TABLE citation_authors_legacy_backup;\n"
				);
			}
			return;
		}

		$this->check_ready();
		$this->ensure_backup($present, $has_authors_table);
		$this->drop_authors_table($has_authors_table);
		$this->drop_columns($present);
		$this->forget_table_cache();
		$this->db->data_cache = array();
		$this->emit(
			"\nThe pre-CSL copies remain in citations_legacy_backup and citation_authors_legacy_backup."
			. " When you have checked the citations, drop them:\n"
			. "  DROP TABLE citations_legacy_backup;\n"
			. "  DROP TABLE citation_authors_legacy_backup;\n"
		);
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup (or the *_legacy_backup tables) if needed.');
	}

	/**
	 * Stop the migration with a message that is shown (the CLI and the admin page do not print exception messages).
	 */
	private function stop($message)
	{
		$this->emit("\n   STOPPED: {$message}\n");
		throw new Exception($message);
	}

	private function is_mysql()
	{
		return in_array($this->db->dbdriver, array('mysql', 'mysqli'), TRUE);
	}

	private function is_sqlsrv()
	{
		return $this->db->dbdriver === 'sqlsrv';
	}

	/**
	 * Every citation must have its document, and the earlier backfill must not have had to fall back for any of them.
	 */
	private function check_ready()
	{
		$this->emit("1. checking that every citation has its document\n");

		if (!$this->has_column('citations', 'metadata')) {
			$this->stop('citations.metadata does not exist: run migration 20261111120001_citations_csl first');
		}

		$missing = (int) $this->db->query('SELECT COUNT(*) AS n FROM citations WHERE metadata IS NULL')->row()->n;
		if ($missing > 0) {
			$this->stop("{$missing} citations have no metadata document; the old columns are the only copy of their data. Nothing was changed.");
		}

		// the backfill marks a citation it could not convert; its document only has a title and a type
		$like = '%backfill_error%';
		$result = $this->db->query('SELECT id FROM citations WHERE metadata LIKE ' . $this->db->escape($like));
		$ids = $result ? array_column($result->result_array(), 'id') : array();
		if (!empty($ids)) {
			$this->stop(
				count($ids) . ' citations (ids ' . implode(', ', array_slice($ids, 0, 20)) . (count($ids) > 20 ? ', ...' : '')
				. ') were not converted completely by the earlier backfill; their old columns hold data the document lacks.'
				. ' Fix them (edit the citation, or remove "backfill_error" from custom.nada once the document is complete) and run this again. Nothing was changed.'
			);
		}
		$this->emit("   ok\n");
	}

	/**
	 * Prefer the full-table backups from migration 20261111120001. If they are missing (older Phase 1),
	 * create them here before dropping live legacy data.
	 */
	private function ensure_backup(array $present, $has_authors_table)
	{
		$this->emit("2. ensure backup tables\n");

		if ($this->table_exists('citations_legacy_backup')) {
			$this->emit("   citations_legacy_backup already present (from Phase 1 or an earlier run)\n");
		} elseif (!empty($present)) {
			$columns = implode(', ', array_map(array($this, 'quote'), array_merge(array('id'), $present)));
			if ($this->is_sqlsrv()) {
				$sql = "SELECT {$columns} INTO citations_legacy_backup FROM citations";
			} elseif ($this->is_mysql()) {
				$sql = "CREATE TABLE `citations_legacy_backup` AS SELECT {$columns} FROM `citations`";
			} else {
				$this->stop('Unsupported database driver: ' . $this->db->dbdriver);
			}
			$this->assert_db_query($this->db->query($sql), 'create citations_legacy_backup');
			$this->forget_table_cache();

			$copied = (int) $this->db->query('SELECT COUNT(*) AS n FROM citations_legacy_backup')->row()->n;
			$total = (int) $this->db->query('SELECT COUNT(*) AS n FROM citations')->row()->n;
			if ($copied !== $total) {
				$this->stop("citations_legacy_backup has {$copied} rows, expected {$total}; nothing was dropped.");
			}
			$this->emit("   + citations_legacy_backup ({$copied} rows; safety copy of remaining legacy columns)\n");
		}

		if ($this->table_exists('citation_authors_legacy_backup')) {
			$this->emit("   citation_authors_legacy_backup already present (from Phase 1 or an earlier run)\n");
		} elseif ($has_authors_table) {
			if ($this->is_sqlsrv()) {
				$sql = 'SELECT * INTO citation_authors_legacy_backup FROM citation_authors';
			} elseif ($this->is_mysql()) {
				$sql = 'CREATE TABLE `citation_authors_legacy_backup` AS SELECT * FROM `citation_authors`';
			} else {
				$this->stop('Unsupported database driver: ' . $this->db->dbdriver);
			}
			$this->assert_db_query($this->db->query($sql), 'create citation_authors_legacy_backup');
			$this->forget_table_cache();
			$copied = (int) $this->db->query('SELECT COUNT(*) AS n FROM citation_authors_legacy_backup')->row()->n;
			$this->emit("   + citation_authors_legacy_backup ({$copied} rows; safety copy)\n");
		}
	}

	private function drop_authors_table($had_authors_table)
	{
		if (!$had_authors_table || !$this->table_exists('citation_authors')) {
			return;
		}
		if (!$this->table_exists('citation_authors_legacy_backup')) {
			$this->stop('citation_authors still exists but citation_authors_legacy_backup does not; refusing to drop the live table.');
		}
		$this->assert_db_query($this->db->query('DROP TABLE ' . $this->quote('citation_authors')), 'drop citation_authors');
		$this->forget_table_cache();
		$this->emit("   - citation_authors dropped (backup kept)\n");
	}

	private function drop_columns(array $present)
	{
		$this->emit("3. drop the old columns\n");
		if (empty($present)) {
			$this->emit("   none left\n");
			return;
		}

		if ($this->is_mysql()) {
			// full-text indexes that still name an old column go first (they were replaced by migration 20261111120001)
			foreach (array('ft_citations', 'ft_cit2') as $index) {
				if ($this->index_exists('citations', $index)) {
					$this->assert_db_query($this->db->query('ALTER TABLE `citations` DROP INDEX `' . $index . '`'), 'drop ' . $index);
				}
			}
			$drops = implode(', ', array_map(function ($c) { return 'DROP COLUMN `' . $c . '`'; }, $present));
			$this->assert_db_query($this->db->query('ALTER TABLE `citations` ' . $drops), 'drop legacy columns');
			$this->emit('   - ' . implode(', ', $present) . "\n");
			return;
		}

		if ($this->is_sqlsrv()) {
			$rebuild_fulltext = $this->sqlsrv_fulltext_uses($present);
			if ($rebuild_fulltext) {
				$this->assert_db_query($this->db->query('DROP FULLTEXT INDEX ON citations'), 'drop citations full-text index');
			}

			foreach ($present as $column) {
				// default constraints (DEFAULT NULL, DEFAULT '0') and indexes on the column must go first
				$default = $this->db->query(
					"SELECT dc.name FROM sys.default_constraints dc
					 JOIN sys.columns c ON c.object_id = dc.parent_object_id AND c.column_id = dc.parent_column_id
					 WHERE dc.parent_object_id = OBJECT_ID('citations') AND c.name = ?",
					array($column)
				)->row_array();
				if ($default) {
					$this->assert_db_query($this->db->query('ALTER TABLE citations DROP CONSTRAINT [' . $default['name'] . ']'), 'drop default of citations.' . $column);
				}
				$indexes = $this->db->query(
					"SELECT DISTINCT i.name FROM sys.indexes i
					 JOIN sys.index_columns ic ON ic.object_id = i.object_id AND ic.index_id = i.index_id
					 JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
					 WHERE i.object_id = OBJECT_ID('citations') AND i.is_primary_key = 0 AND i.name IS NOT NULL AND c.name = ?",
					array($column)
				)->result_array();
				foreach ($indexes as $index) {
					$this->assert_db_query($this->db->query('DROP INDEX [' . $index['name'] . '] ON citations'), 'drop index ' . $index['name']);
				}
			}

			$drops = implode(', ', array_map(function ($c) { return '[' . $c . ']'; }, $present));
			$this->assert_db_query($this->db->query('ALTER TABLE citations DROP COLUMN ' . $drops), 'drop legacy columns');
			$this->emit('   - ' . implode(', ', $present) . "\n");

			if ($rebuild_fulltext) {
				$this->assert_db_query($this->db->query(
					'CREATE FULLTEXT INDEX ON citations (title, container_title, authors_text, search_text, notes) KEY INDEX pk_idx_citations'
				), 'citations full-text index');
				$this->emit("   + citations full-text index rebuilt on the new columns\n");
			}
			return;
		}

		throw new Exception('Unsupported database driver: ' . $this->db->dbdriver);
	}

	/**
	 * True when the SQL Server full-text index of citations includes one of the columns.
	 */
	private function sqlsrv_fulltext_uses(array $columns)
	{
		$rows = $this->db->query(
			"SELECT c.name FROM sys.fulltext_index_columns fic
			 JOIN sys.columns c ON c.object_id = fic.object_id AND c.column_id = fic.column_id
			 WHERE fic.object_id = OBJECT_ID('citations')"
		);
		if (!$rows) {
			return false;
		}
		foreach ($rows->result_array() as $row) {
			if (in_array($row['name'], $columns, true)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Column check straight from the catalog (not CI's cached column list)
	 */
	private function has_column($table, $column)
	{
		if ($this->is_sqlsrv()) {
			return $this->sqlsrv_has_column($table, $column);
		}
		$row = $this->db->query(
			'SELECT COUNT(*) AS n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
			array($table, $column)
		)->row_array();
		return (int) $row['n'] > 0;
	}

	private function quote($identifier)
	{
		return $this->is_sqlsrv() ? '[' . $identifier . ']' : '`' . $identifier . '`';
	}
}
