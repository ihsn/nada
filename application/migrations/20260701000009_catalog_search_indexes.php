<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Citation and catalog search indexes.
 *
 * Split out of 20260701000003 so index builds on large SQL Server tables
 * do not share a web request with schema changes. Each index is created
 * only when missing, so a timed-out run can be started again.
 *
 * SQL Server: also the full-text index on citations (keyword and duplicate
 * search). SQL Server allows one full-text index per table, so it covers the
 * columns of both MySQL citation full-text indexes.
 */
class Migration_Catalog_search_indexes extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260322000001_add_citation_indexes.php',
			'20260701120001_add_search_performance_indexes.php',
		));
		$this->add_citations_fulltext_sqlsrv();
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

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
