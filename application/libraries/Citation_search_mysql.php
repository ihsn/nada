<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH.'libraries/Citation_search_filters.php');

/**
 * Citation search for MySQL / MariaDB.
 *
 * Finds the matching citation ids (full-text on citations.search_text; admin searches with include_notes
 * also match notes) and loads the rows with Citation_model::get_rows_by_ids().
 */
class Citation_search_mysql
{
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
		$keywords = Citation_search_filters::keywords($filter);

		$db->select('SQL_CALC_FOUND_ROWS citations.id', FALSE);
		$db->from('citations');
		Citation_search_filters::join_users($db);
		Citation_search_filters::apply($db, $filter, $published, $repositoryid);

		$sort_on_rank = false;
		if ($keywords !== '') {
			$match = sprintf('MATCH(citations.search_text) AGAINST(%s IN BOOLEAN MODE)', $db->escape($keywords));
			if (Citation_search_filters::include_notes($filter)) {
				$db->where('(' . $match . ' OR ' . Citation_search_filters::like($db, 'citations.notes', $keywords) . ')', NULL, FALSE);
			} else {
				$db->where($match, NULL, FALSE);
			}
			$db->select($match . ' AS rank_', FALSE);
			$sort_on_rank = true;
		}

		if (!Citation_search_filters::order_by($db, $sort_by, $sort_order)) {
			if ($sort_on_rank) {
				$db->order_by('rank_', 'DESC');
			}
			$db->order_by('citations.changed', 'DESC');
		}

		$db->limit((int) $limit, (int) $offset);
		$query = $db->get();

		if (!$query) {
			$this->search_found_rows = 0;
			return FALSE;
		}

		$ids = array_column($query->result_array(), 'id');
		$found = $db->query('SELECT FOUND_ROWS() as rowcount')->row_array();
		$this->search_found_rows = (int) $found['rowcount'];

		return $this->ci->Citation_model->get_rows_by_ids($ids);
	}

	function search_count()
	{
		return $this->search_found_rows;
	}
}
