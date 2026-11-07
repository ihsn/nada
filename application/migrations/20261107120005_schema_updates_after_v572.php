<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Schema steps added after v5.7.2.
 *
 * - Search index tables: re-runs the archived catalog_search_queue_filters step, which creates search_index_meta
 *   and adds search_index_queue.priority and search_index_meta.nada_ai_url where they are missing.
 * - resources text columns widened as install/schema.*.sql defines them (and Unicode on SQL Server; never
 *   narrowed).
 * - SQL Server: the full-text index on citations (keyword and duplicate search). SQL Server allows one full-text
 *   index per table, so it covers the columns of both MySQL citation full-text indexes.
 *
 * Every step skips work that is already applied, so sites that already have these changes are left as they are.
 */
class Migration_Schema_updates_after_v572 extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260909120002_catalog_search_queue_filters.php',
		));
		$this->widen_resources_columns();
		$this->add_citations_fulltext_sqlsrv();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	/** resources text columns as install/schema.*.sql defines them: column => [max length (0 = unlimited), NOT NULL] */
	private $resources_columns = array(
		'dctype'      => array(255, FALSE),
		'filename'    => array(500, FALSE),
		'dcformat'    => array(255, FALSE),
		'title'       => array(500, TRUE),
		'subtitle'    => array(500, FALSE),
		'author'      => array(500, FALSE),
		'dcdate'      => array(45, FALSE),
		'country'     => array(100, FALSE),
		'language'    => array(255, FALSE),
		'contributor' => array(500, FALSE),
		'publisher'   => array(500, FALSE),
		'rights'      => array(0, FALSE),
		'subjects'    => array(0, FALSE),
		'description' => array(0, FALSE),
		'abstract'    => array(0, FALSE),
		'toc'         => array(0, FALSE),
	);

	private function widen_resources_columns()
	{
		if (!$this->table_exists('resources')) {
			return;
		}
		$driver = $this->db->dbdriver;
		foreach ($this->resources_columns as $column => $spec) {
			list($length, $not_null) = $spec;
			if (!$this->column_exists('resources', $column)) {
				continue;
			}
			if (in_array($driver, array('mysql', 'mysqli'), TRUE)) {
				$row = $this->db->query(
					'SELECT DATA_TYPE AS t, CHARACTER_MAXIMUM_LENGTH AS len FROM information_schema.COLUMNS
					 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
					array('resources', $column)
				)->row_array();
				// text columns longer than the target (mediumtext) are left as they are
				$current = (int) $row['len'];
				$target  = $length === 0 ? 65535 : $length;
				if ($current >= $target) {
					continue;
				}
				$type = $length === 0 ? 'TEXT' : 'VARCHAR(' . $length . ')';
				$this->assert_db_query(
					$this->db->query('ALTER TABLE `resources` MODIFY `' . $column . '` ' . $type . ($not_null ? ' NOT NULL' : ' NULL')),
					'widen resources.' . $column
				);
			} elseif ($driver === 'sqlsrv') {
				$row = $this->db->query(
					'SELECT DATA_TYPE AS t, CHARACTER_MAXIMUM_LENGTH AS len FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?',
					array('resources', $column)
				)->row_array();
				$current = (int) $row['len'];
				$wide_enough = $current === -1 || ($length > 0 && $current >= $length);
				if ($row['t'] === 'nvarchar' && $wide_enough) {
					continue;
				}
				$type = $length === 0 ? 'NVARCHAR(MAX)' : 'NVARCHAR(' . max($length, $current) . ')';
				// ALTER COLUMN refuses a column with a default constraint (older schemas made one for DEFAULT NULL):
				// drop it, alter, and put the same default back
				$default = $this->db->query(
					"SELECT dc.name, dc.definition FROM sys.default_constraints dc
					 JOIN sys.columns c ON c.object_id = dc.parent_object_id AND c.column_id = dc.parent_column_id
					 WHERE dc.parent_object_id = OBJECT_ID('resources') AND c.name = ?",
					array($column)
				)->row_array();
				if ($default) {
					$this->assert_db_query(
						$this->db->query('ALTER TABLE resources DROP CONSTRAINT [' . $default['name'] . ']'),
						'drop default of resources.' . $column
					);
				}
				$this->assert_db_query(
					$this->db->query('ALTER TABLE resources ALTER COLUMN [' . $column . '] ' . $type . ($not_null ? ' NOT NULL' : ' NULL')),
					'widen resources.' . $column
				);
				if ($default && strtoupper(trim($default['definition'], '() ')) !== 'NULL') {
					$this->assert_db_query(
						$this->db->query('ALTER TABLE resources ADD CONSTRAINT [' . $default['name'] . '] DEFAULT ' . $default['definition'] . ' FOR [' . $column . ']'),
						'restore default of resources.' . $column
					);
				}
			}
		}
		if ($driver === 'sqlsrv' && !$this->index_exists('resources', 'idx_res_resource_type') && $this->column_exists('resources', 'resource_type')) {
			$this->assert_db_query(
				$this->db->query('CREATE NONCLUSTERED INDEX idx_res_resource_type ON resources (resource_type)'),
				'idx_res_resource_type'
			);
		}
		$this->forget_table_cache();
	}

	private function add_citations_fulltext_sqlsrv()
	{
		if ($this->db->dbdriver !== 'sqlsrv' || !$this->table_exists('citations')) {
			return;
		}
		$row = $this->db->query("SELECT FULLTEXTSERVICEPROPERTY('IsFullTextInstalled') AS installed")->row_array();
		if (empty($row['installed'])) {
			$this->emit("  citations full-text index skipped: Full-Text Search is not installed on this SQL Server\n");
			return;
		}
		$present = $this->db->query("SELECT 1 AS present FROM sys.fulltext_indexes WHERE object_id = OBJECT_ID('citations')");
		if ($present && $present->num_rows() > 0) {
			return;
		}
		$catalogs = $this->db->query('SELECT 1 AS present FROM sys.fulltext_catalogs');
		if (!$catalogs || $catalogs->num_rows() === 0) {
			$this->assert_db_query($this->db->query('CREATE FULLTEXT CATALOG ft AS DEFAULT'), 'CREATE FULLTEXT CATALOG ft');
		}
		if (!$this->index_exists('citations', 'pk_idx_citations')) {
			$this->assert_db_query(
				$this->db->query('CREATE UNIQUE INDEX pk_idx_citations ON dbo.citations(id)'),
				'pk_idx_citations'
			);
		}
		$this->assert_db_query($this->db->query(
			'CREATE FULLTEXT INDEX ON citations (title, subtitle, alt_title, authors, editors, translators, '
			. 'organization, abstract, keywords, notes, doi) KEY INDEX pk_idx_citations'
		), 'citations full-text index');
		$this->emit("  citations full-text index created\n");
	}
}
