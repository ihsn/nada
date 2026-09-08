<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Catalog search filter indexes.
 *
 * The catalog sidebar filters resolve as `surveys.id IN (SELECT sid FROM <table> WHERE <filter_col> ...)`.
 * On MySQL none of those child tables were indexed on the filtered column, so country, collection,
 * user-facet and region filters ran full table scans on every search request, and the year and tag
 * filters could only do full index scans (their existing indexes lead with `sid`, not the filtered column).
 *
 * SQL Server already ships equivalent covering indexes in install/schema.sqlsrv.sql
 * (idx_survey_countries_cid, idx_survey_repos_repositoryid, idx_survey_years_year_sid,
 * idx_survey_facets_term_id, idx_survey_tags_tag). This migration brings MySQL to parity and adds
 * the region_countries index that neither driver had.
 *
 * Composite (filter_col, sid) on MySQL is the equivalent of SQL Server's
 * INDEX (filter_col) INCLUDE (sid) — both let the subquery seek and return sid from the index alone.
 */
class Migration_Catalog_search_filter_indexes extends MY_Migration {

	/**
	 * @var array<int, array{table: string, name: string, cols: string[]}>
	 */
	private $indexes = array(
		// country facet: SELECT sid FROM survey_countries WHERE cid IN (...)
		array('table' => 'survey_countries', 'name' => 'idx_survey_countries_cid',      'cols' => array('cid', 'sid')),
		// collection/repository filter: SELECT sid FROM survey_repos WHERE repositoryid IN (...)
		array('table' => 'survey_repos',     'name' => 'idx_survey_repos_repositoryid', 'cols' => array('repositoryid', 'sid')),
		// survey_repos is also joined on sid by the sqlsrv driver and the collections subquery
		array('table' => 'survey_repos',     'name' => 'idx_survey_repos_sid',          'cols' => array('sid')),
		// user-defined facets: SELECT sid FROM survey_facets WHERE term_id IN (...)
		array('table' => 'survey_facets',    'name' => 'idx_survey_facets_term_id',     'cols' => array('term_id', 'sid')),
		array('table' => 'survey_facets',    'name' => 'idx_survey_facets_sid',         'cols' => array('sid')),
		// year range: SELECT sid FROM survey_years WHERE data_coll_year BETWEEN ... (existing index leads with sid)
		array('table' => 'survey_years',     'name' => 'idx_survey_years_year_sid',     'cols' => array('data_coll_year', 'sid')),
		// tag facet: SELECT sid FROM survey_tags WHERE tag IN (...) (existing uq_tag leads with sid)
		array('table' => 'survey_tags',      'name' => 'idx_survey_tags_tag',           'cols' => array('tag', 'sid')),
		// region facet joins region_countries -> survey_countries; neither column was indexed
		array('table' => 'region_countries', 'name' => 'idx_region_countries_region',   'cols' => array('region_id', 'country_id')),
	);

	public function up()
	{
		$driver = $this->db->dbdriver;

		foreach ($this->indexes as $index) {
			if (! $this->db->table_exists($index['table'])) {
				continue;
			}

			if (in_array($driver, array('mysql', 'mysqli'), true)) {
				$this->create_index_mysql($index);
			} elseif ($driver === 'sqlsrv') {
				$this->create_index_sqlsrv($index);
			}
		}
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Drop the idx_* indexes manually if needed.');
	}

	/**
	 * @param array{table: string, name: string, cols: string[]} $index
	 */
	private function create_index_mysql(array $index)
	{
		if ($this->mysql_index_exists($index['table'], $index['name'])) {
			return;
		}

		// Skip if the table lacks any of the columns (older/customised schemas).
		foreach ($index['cols'] as $col) {
			if (! $this->db->field_exists($col, $index['table'])) {
				return;
			}
		}

		$cols = '`' . implode('`,`', $index['cols']) . '`';

		$this->db->query(
			'ALTER TABLE `' . $index['table'] . '` ADD INDEX `' . $index['name'] . '` (' . $cols . ')'
		);
	}

	/**
	 * @param array{table: string, name: string, cols: string[]} $index
	 */
	private function create_index_sqlsrv(array $index)
	{
		if ($this->sqlsrv_index_exists($index['table'], $index['name'])) {
			return;
		}

		foreach ($index['cols'] as $col) {
			if (! $this->db->field_exists($col, $index['table'])) {
				return;
			}
		}

		// Mirror the INCLUDE(sid) shape already used in schema.sqlsrv.sql: lead column indexed,
		// remaining columns carried as included columns.
		$lead     = array_shift($index['cols']);
		$included = $index['cols'];

		$sql = 'CREATE NONCLUSTERED INDEX ' . $index['name']
			. ' ON ' . $index['table'] . ' (' . $lead . ' ASC)';

		if (! empty($included)) {
			$sql .= ' INCLUDE (' . implode(',', $included) . ')';
		}

		$this->db->query($sql);
	}

	private function mysql_index_exists($table, $index_name)
	{
		$sql = 'SELECT COUNT(*) AS n FROM information_schema.STATISTICS
			WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?';

		$row = $this->db->query($sql, array($table, $index_name))->row_array();

		return ! empty($row['n']);
	}

	private function sqlsrv_index_exists($table, $index_name)
	{
		$sql = 'SELECT COUNT(*) AS n FROM sys.indexes
			WHERE name = ? AND object_id = OBJECT_ID(?)';

		$row = $this->db->query($sql, array($index_name, $table))->row_array();

		return ! empty($row['n']);
	}
}
