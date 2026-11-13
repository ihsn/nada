<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH.'libraries/Citation_search_filters.php');

/**
 * Citation search for SQL Server.
 *
 * Finds the matching citation ids with the citations full-text index and loads the rows with
 * Citation_model::get_rows_by_ids(). The full-text index also covers notes (one index per table on
 * SQL Server), so every query names the columns it searches; notes only for admin searches (include_notes).
 */
class Citation_search_sqlsrv
{
	/** Columns a keyword search matches (public). */
	const FULLTEXT_COLUMNS = 'title,container_title,authors_text,search_text';

	var $ci;
	var $errors            = array();
	var $search_found_rows = 0;

	function __construct($params = array())
	{
		$this->ci =& get_instance();
	}

	function search($limit = NULL, $offset = NULL, $filter = NULL, $sort_by = NULL, $sort_order = NULL, $published = NULL, $repositoryid = NULL)
	{
		$db = $this->ci->db;
		$limit = max(1, (int) $limit);
		$offset = max(0, (int) $offset);
		$keywords = Citation_search_filters::keywords($filter);
		$include_notes = Citation_search_filters::include_notes($filter);

		// rows
		$db->select('citations.id', FALSE);
		$db->from('citations');
		Citation_search_filters::join_users($db);
		Citation_search_filters::apply($db, $filter, $published, $repositoryid);

		$sort_on_rank = false;
		if ($keywords !== '') {
			if ($include_notes) {
				$db->where($this->keyword_predicate($keywords, true), NULL, FALSE);
			} else {
				// FREETEXTTABLE gives the rank; top_n must cover this page
				$db->join(
					sprintf('FREETEXTTABLE(citations,(%s),%s,%d) AS KEY_TBL', self::FULLTEXT_COLUMNS, $db->escape($keywords), $offset + $limit),
					'citations.id = KEY_TBL.[KEY]',
					'inner',
					FALSE // CI would mangle the [KEY] column and the function call
				);
				$db->select('KEY_TBL.RANK AS rank_', FALSE);
				$sort_on_rank = true;
			}
		}

		if (!Citation_search_filters::order_by($db, $sort_by, $sort_order)) {
			if ($sort_on_rank) {
				$db->order_by('KEY_TBL.RANK', 'DESC');
			}
			$db->order_by('citations.changed', 'DESC');
			$db->order_by('citations.id', 'DESC');
		}

		$db->limit($limit, $offset);
		$query = $db->get();

		if (!$query) {
			$this->search_found_rows = 0;
			return FALSE;
		}
		$ids = array_column($query->result_array(), 'id');

		// count (FREETEXT predicate: not capped like FREETEXTTABLE top_n)
		$db->select('COUNT(citations.id) AS rows_found', FALSE);
		$db->from('citations');
		Citation_search_filters::apply($db, $filter, $published, $repositoryid);
		if ($keywords !== '') {
			$db->where($this->keyword_predicate($keywords, $include_notes), NULL, FALSE);
		}
		$count = $db->get();
		$this->search_found_rows = $count ? (int) $count->row()->rows_found : 0;

		return $this->ci->Citation_model->get_rows_by_ids($ids);
	}

	private function keyword_predicate($keywords, $include_notes)
	{
		$db = $this->ci->db;
		$predicate = sprintf('FREETEXT((%s), %s)', self::FULLTEXT_COLUMNS, $db->escape($keywords));
		if ($include_notes) {
			$predicate = '(' . $predicate . ' OR ' . Citation_search_filters::like($db, 'citations.notes', $keywords) . ')';
		}
		return $predicate;
	}

	function search_count()
	{
		return $this->search_found_rows;
	}
}
