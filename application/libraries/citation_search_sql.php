<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Citation Search Class
 * 
 * 
 *
 * @package		NADA 3
 * @subpackage	Libraries
 * @category	Citation Search
 * @author		Mehmood Asghar
 * @link		-
 *
 */ 
class Citation_search_sql{    	
	
	var $ci;
	
	var $errors=array();
	
			//fields returned by select
	var	$select_fields='citations.id,
						citations.title,
						citations.subtitle,
						citations.alt_title,
						citations.authors,
						citations.editors,
						citations.translators,
						citations.changed,
						citations.created,
						citations.published,
						citations.volume,
						citations.issue,
						citations.idnumber,
						citations.edition,
						citations.place_publication,
						citations.place_state,
						citations.publisher,
						citations.publication_medium,
						citations.url,
						citations.page_from,
						citations.page_to,
						citations.data_accessed,
						citations.organization,
						citations.ctype,
						citations.pub_day,
						citations.pub_month,
						citations.pub_year,
						citations.abstract,
						citations.owner,
						citations.notes,
						citations.flag,
						count(survey_citations.sid) as survey_count'; 

	var $groupby_fields='citations.id,
						citations.title,
						citations.subtitle,
						citations.alt_title,
						citations.authors,
						citations.editors,
						citations.translators,
						citations.changed,
						citations.created,
						citations.published,
						citations.volume,
						citations.issue,
						citations.idnumber,
						citations.edition,
						citations.place_publication,
						citations.place_state,
						citations.publisher,
						citations.publication_medium,
						citations.url,
						citations.page_from,
						citations.page_to,
						citations.data_accessed,
						citations.organization,
						citations.ctype,
						citations.pub_day,
						citations.pub_month,
						citations.pub_year,
						citations.owner,
						citations.notes,
						citations.flag,
						citations.abstract';	
	
	//allowed_fields
	var	$db_fields=array(
					'title'=>'citations.title',
					'subtitle'=>'citations.subtitle',
					'alt_title'=>'citations.alt_title',
					'authors'=>'citations.authors',
					'editors'=>'citations.editors',
					'translators'=>'citations.translators',
					'place_publication'=>'citations.place_publication',
					'publisher'=>'citations.publisher',
					'url'=>'citations.url',
					'place_state'=>'citations.place_state',
					'country'=>'surveys.nation',
					'pub_year'=>'citations.pub_year',
					'survey_count'=>'citations.title',
					'changed'=>'citations.changed',
					'ctype'=>'citations.ctype'
					);
		
	//fields to search when search=ALL FIELDS
	var	$all_fields=array(
					'citations.title',
					'citations.subtitle',
					'citations.alt_title',
					'citations.authors',
					'citations.url',
					//'citations.pub_year',
					'surveys.nation'
					);
		
    /**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 */
	function __construct($params = array())
	{
		$this->ci=& get_instance();
		
		if (count($params) > 0)
		{
			$this->initialize($params);
		}
		
		//$this->ci->output->enable_profiler(TRUE);
	}
	
	function initialize($params=array())
	{
		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				if (isset($this->$key))
				{
					$this->$key = $val;
				}
			}
		}
	}


    function search($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL)
    {
					
		//select columns for output
		$this->ci->db->select($this->select_fields, FALSE);
		$this->ci->db->from('citations');
		$this->ci->db->join('survey_citations', 'survey_citations.citationid = citations.id','left');
		$this->ci->db->join('surveys', 'survey_citations.sid = surveys.id','left');
		$this->ci->db->group_by($this->groupby_fields);
		
		//set where
		if ($filter)
		{			
			foreach($filter as $f)
			{
				//split keyword by space
				$keywords_array=explode(" ", trim($f['keywords']));
				
				foreach($keywords_array as $keyword)
				{
					if (trim($keyword)!="" && strlen($keyword)>2)
					{
						//search only in the allowed fields
						if (array_key_exists($f['field'],$this->db_fields))
						{
							$this->ci->db->like($this->db_fields[$f['field']], trim($keyword)); 
						}
						else if ($f['field']=='all')
						{
							foreach($this->all_fields as $field)
							{
								$this->ci->db->or_like($field, trim($keyword)); 
							}
						}
					}
				}
			}
		}
				
		//set order by
		if ($sort_by!='' && $sort_order!='')
		{
			if (array_key_exists($sort_by,$this->db_fields))
			{
				$this->ci->db->order_by($this->db_fields[$sort_by], $sort_order); 
			}
			else
			{
				$this->ci->db->order_by('citations.title', $sort_order); 
			}			
		}
		
		//set Limit clause
	  	$this->ci->db->limit($limit, $offset);
        $query= $this->ci->db->get();
		
		if ($query)
		{
			$result=$query->result_array();
			
			//get total search result count
			$query_found_rows=$this->ci->db->query('SELECT count(*) as rows_count',FALSE)->row_array();
			$this->search_found_rows=$query_found_rows['rows_count'];
			
			//find authors for citations
			foreach($result as $key=>$row)
			{
				$result[$key]['authors']=$this->get_citation_authors($row['id'],'author');
			}
			
			return $result;
		}
				
		return FALSE;
    }
	
	
	function get_citation_authors($citationid,$type)
	{
		$this->ci->db->select('*'); 
		$this->ci->db->where('cid', $citationid); 
		$this->ci->db->where('author_type', $type); 
		$query=$this->ci->db->get("citation_authors");
		
		if($query)
		{
			return $query->result_array();
		}
		return FALSE;
	}
	
    function search_count()
    {
		return $this->search_found_rows;
    }

	function _where($filter)
	{
		foreach($filter as $f)
		{
			//split keyword by space
			$keywords_array=explode(" ", trim($f['keywords']));
			
			foreach($keywords_array as $keyword)
			{
				if (trim($keyword)!="" && strlen($keyword)>2)
				{
					//search only in the allowed fields
					if (array_key_exists($f['field'],$db_fields))
					{
						$this->ci->db->like($db_fields[$f['field']], trim($keyword)); 
					}
					else if ($f['field']=='all')
					{
						foreach($all_fields as $field)
						{
							$this->ci->db->or_like($field, trim($keyword)); 
						}
					}
				}
			}
		}		
	}
	
}// END Search class

/* End of file Catalog_search.php */
/* Location: ./application/libraries/Catalog_search.php */