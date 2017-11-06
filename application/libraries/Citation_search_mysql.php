<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Citation Search Class for MySQL
 * 
 * 
 *
 * @package		NADA 3
 * @subpackage	Libraries
 * @category	Citation Search MySQL
 * @author		Mehmood Asghar
 * @link		-
 *
 */ 
class Citation_search_mysql{ 
	
	var $ci;
	
	var $errors=array();
	var $search_found_rows=0;
	
		
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


    //search
    function search($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL,$published=NULL,$repositoryid=NULL)
    {
		//fields returned by select
		$select_fields='SQL_CALC_FOUND_ROWS 
						citations.id,
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
						citations.keywords,
						citations.notes,
						citations.doi,
						citations.flag,
						count(survey_citations.sid) as survey_count,
						citations.owner';
						
		//select columns for output
		$this->ci->db->select($select_fields, FALSE);
	
		//allowed_fields
		$db_fields=array(
					'id'=>'citations.id',
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
					'survey_count'=>'survey_count',
					'changed'=>'citations.changed',
					'created'=>'citations.created',
					'ctype'=>'citations.ctype',
					'keywords'=>'citations.keywords',
					'notes'=>'citations.notes',
					'doi'=>'citations.doi',
					'flag'=>'citations.flag',
					'published'=>'citations.published',
					'owner'=>'citations.owner'
					);
		
		//fields to search when search=ALL FIELDS
		$all_fields=array(
					'citations.title',
					'citations.subtitle',
					'citations.alt_title',
					'citations.authors',
					'citations.url',
					'citations.pub_year',
					'surveys.nation',
					'citations.keywords',
					'citations.doi'
					);
		
		$this->ci->db->from('citations');
		$this->ci->db->join('survey_citations', 'survey_citations.citationid = citations.id','left');
		$this->ci->db->join('surveys', 'survey_citations.sid = surveys.id','left');
		
		//filter by repository if set
		if($repositoryid!=NULL && strtolower($repositoryid)!='central')
		{
			$this->ci->db->join('survey_repos', 'surveys.id = survey_repos.sid','inner');
			$this->ci->db->where('survey_repos.repositoryid',$repositoryid);
		}
		
		$this->ci->db->group_by('citations.id');
		
		$fulltext_index='citations.title,citations.subtitle,citations.authors,citations.doi,citations.keywords';
		$country_fulltext_index='surveys.nation';

		if (is_numeric($published))
		{
			$this->ci->db->where ('citations.published',$published);
		}
		
		//set where
		if ($filter)
		{			
			foreach($filter as $f)
			{	
				$keywords=trim($f['keywords']);
				if (trim($keywords)!="" && strlen($keywords)>=3)
				{
					//search only in the allowed fields
					if ($f['field']!='' &&  array_key_exists($f['field'],$db_fields))
					{
						//$this->ci->db->like($db_fields[$f['field']], trim($keyword)); 
						$this->ci->db->where(sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)',$f['field'],$this->ci->db->escape($keywords)));
					}
					else if ($f['field']=='all')
					{
							//$this->ci->db->or_like($field, trim($keyword)); 
							$this->ci->db->where(sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)',$fulltext_index,$this->ci->db->escape($keywords)));
							$this->ci->db->or_where(sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)',$country_fulltext_index,$this->ci->db->escape($keywords)));
					}
				}
				if ( ($f['field']=='notes' ||  $f['field']=='flag') && $f['keywords']=='*')
				{
					$this->ci->db->where(sprintf("%s !=''",$f['field']));
				}

			}
		}				
				
		//set order by
		if ($sort_by!='' && $sort_order!='')
		{
			if (array_key_exists($sort_by,$db_fields))
			{
				$this->ci->db->order_by($sort_by, $sort_order); 
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
			$query_found_rows=$this->ci->db->query('SELECT FOUND_ROWS() as rowcount',FALSE)->row_array();
			$this->search_found_rows=$query_found_rows['rowcount'];

			//find authors for citations
			foreach($result as $key=>$row)
			{
				$result[$key]['authors']=$this->get_citation_authors($row['id'],'author');
			}
			
			return $result;
		}				
		return FALSE;
    }
	
	
	
  	/**
	*
	* Search on citation survey country
	*
	* return arrayy of citation IDs
	*
	* TODO: remove, no longer in use
 	**/
	function search_citation_by_country($keyword=NULL)
	{
		$this->ci->db->select('citationid');
		$this->ci->db->from('survey_citations');
		$this->ci->db->join('surveys', 'survey_citations.sid = surveys.id','inner');
		$this->ci->db->group_by('survey_citations.citationid');
		$this->ci->db->like('surveys.nation',$keyword);
        $query= $this->ci->db->get();

		$output=array();			

		if ($query)
		{
			$result=$query->result_array();
			
			foreach($result as $row)
			{
				$output[]=$row['citationid'];
			}			
		}		

		return $output;
	}
	
	
	
	
    function search_count()
    {
		return $this->search_found_rows;
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

}// END Search class

/* End of file Citation_search_mysql.php */
/* Location: ./application/libraries/Citation_search_mysql.php */