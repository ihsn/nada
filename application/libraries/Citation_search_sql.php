<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH.'libraries/Citation_search_filters.php');

/**
 * Citation search for database drivers without a full-text engine (not MySQL / SQL Server):
 * every keyword must appear (LIKE) in citations.search_text (or notes, for admin searches).
 */
class Citation_search_sql
{
	var $ci;
	var $errors=array();
	var $search_found_rows=0;

	function __construct($params = array())
	{
		$this->ci=& get_instance();
	}

	function search($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL,$published=NULL,$repositoryid=NULL)
	{
		$db=$this->ci->db;

		$db->select('COUNT(citations.id) AS rows_found', FALSE);
		$db->from('citations');
		$this->where($filter,$published,$repositoryid);
		$count=$db->get();
		$this->search_found_rows=$count ? (int)$count->row()->rows_found : 0;

		if ($this->search_found_rows<1){
			return array();
		}

		$db->select('citations.id', FALSE);
		$db->from('citations');
		Citation_search_filters::join_users($db);
		$this->where($filter,$published,$repositoryid);
		if (!Citation_search_filters::order_by($db, $sort_by, $sort_order)){
			$db->order_by('citations.changed','DESC');
			$db->order_by('citations.id','DESC');
		}
		$db->limit((int)$limit, (int)$offset);
		$query=$db->get();

		if (!$query){
			return FALSE;
		}

		return $this->ci->Citation_model->get_rows_by_ids(array_column($query->result_array(),'id'));
	}

	private function where($filter,$published,$repositoryid)
	{
		$db=$this->ci->db;
		Citation_search_filters::apply($db, $filter, $published, $repositoryid);

		$keywords=Citation_search_filters::keywords($filter);
		if ($keywords===''){
			return;
		}

		$include_notes=Citation_search_filters::include_notes($filter);
		foreach(preg_split('/\s+/',$keywords) as $word){
			if (strlen($word)<3){
				continue;
			}
			$db->where('('.Citation_search_filters::like($db,'citations.search_text',$word)
				.($include_notes ? ' OR '.Citation_search_filters::like($db,'citations.notes',$word) : '').')', NULL, FALSE);
		}
	}

	function search_count()
	{
		return $this->search_found_rows;
	}
}
