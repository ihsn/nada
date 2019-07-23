<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Citation Search Class for SQLSRV
 * 
 * 
 *
 */ 
class Citation_search_sqlsrv{ 
	
	var $ci;
	
	var $errors=array();
	var $search_found_rows=0;
	var $sort_on_rank=false;

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
						citations.keywords,
						citations.notes,
						citations.doi,
						citations.flag,
						citations.url_status,
						citations.owner,
						user_changed.username as changed_by_user,
						user_created.username as created_by_user';
						//count(survey_citations.sid) as survey_count,
		
		//should have all the fields from $select_fields
		var $group_fields='citations.id,
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
						citations.owner,
						citations.url_status,
						user_changed.username,
						user_created.username,
						citations.url_status'
						;	
	
		//allowed_fields
		var $db_fields=array(
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
					//'survey_count'=>'survey_count',
					'changed'=>'citations.changed',
					'created'=>'citations.created',
					'ctype'=>'citations.ctype',
					'keywords'=>'citations.keywords',
					'notes'=>'citations.notes',
					'doi'=>'citations.doi',
					'flag'=>'citations.flag',
					'published'=>'citations.published',
					'owner'=>'citations.owner',
					'changed_by_user'=>'user_changed.username',
					'created_by_user'=>'user_created.username',
					'url_status'=>'citations.url_status'
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

	
	 function build_search_options($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL,$published=NULL,$repositoryid=NULL)
	 {
			$where=array();//all where statements are combined in the end as a custom where to overcome the AR limits
			
			$this->ci->db->from('citations');
			
			//$this->ci->db->join('survey_citations', 'survey_citations.citationid = citations.id','left');
			//$this->ci->db->join('surveys', 'survey_citations.sid = surveys.id','left');
			
			//user created the citation
			$this->ci->db->join('users user_created', 'citations.created_by = user_created.id','left');
			
			//user changed citation
			$this->ci->db->join('users user_changed', 'citations.changed_by = user_changed.id','left');
			
			//filter by repository if set
			if($repositoryid!=NULL && strtolower($repositoryid)!='central')
			{
				//$this->ci->db->join('survey_repos', 'surveys.id = survey_repos.sid','inner');
				//$this->ci->db->where('survey_repos.repositoryid',$repositoryid);
				
				$this->ci->db->where(sprintf(' citations.id in (select citationid from survey_citations inner join survey_repos on survey_citations.sid=survey_repos.sid where survey_repos.repositoryid=%s) ',$this->ci->db->escape($repositoryid)));
			}
			
			if (is_numeric($published))
			{
				$this->ci->db->where ('citations.published',$published);
			}

			//$this->ci->db->group_by($this->group_fields);
			
			$fulltext_index='ft_keywords';
			$country_fulltext_index='surveys.nation';
			
			$this->sort_on_rank=false;		
			
			//set where
			if ($filter)
			{
				foreach($filter as $search_field=>$keywords)
				{					
					switch($search_field)
					{
						case 'keywords':
							if (trim($keywords)==""){break;}
							
							$freetext_join=sprintf('freetexttable (citations,(ft_keywords),%s,%d) as KEY_TBL',$this->ci->db->escape($keywords),$limit);
							$this->ci->db->join($freetext_join, 'citations.id = KEY_TBL.KEY','inner');
							$this->sort_on_rank=true;
							
							break;
						
						case 'published':
							if (is_numeric($keywords))
							{
								$this->ci->db->where ('citations.published',$keywords);
							}
							break;
						
						case 'flag':
							if ($keywords!="")
							{
								$this->ci->db->where_in ('citations.flag',$keywords);
							}
							break;
						
						case 'user':
							if (is_array($keywords) && count($keywords)>0)
							{
								$this->ci->db->where_in('changed_by',$keywords);
							}
							break;
						
						case 'has_notes':
							if ($keywords!="")
							{
								$this->ci->db->where(" (notes IS NOT NULL and notes !='') ",NULL, FALSE);
							}
							break;
						
						case 'no_survey_attached':
							if ($keywords!="")
							{
								$this->ci->db->where(" citations.id NOT IN (select citationid from survey_citations)",NULL,FALSE);
							}						
							break;
						
						case 'url_status':
							if ($keywords!="")
							{
								$this->ci->db->where_in ('citations.url_status',$keywords);
							}
							break;	
						
						case 'ctype':
							if (is_array($keywords)){                            
								$this->ci->db->where_in ('citations.ctype',$keywords);
							}
							break; 
						
						case 'from':                        
							if (strlen($keywords)==4 && is_numeric($keywords)){
								$this->ci->db->where ('citations.pub_year >=',intval($keywords),false);
							}
							break;  
                    	case 'to':                        
							if (strlen($keywords)==4 && is_numeric($keywords)){
								$this->ci->db->where ('citations.pub_year <=',intval($keywords),false);
							}
							break;    
					}
				}
			}
	 }
	

    //search
    function search($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL,$published=NULL,$repositoryid=NULL)
    {
		//build where
		$this->build_search_options($limit,$offset,$filter,$sort_by,$sort_order,$published,$repositoryid);
		
		//set order by
		if ($sort_by!='' && $sort_order!='')
		{
			if (array_key_exists($sort_by,$this->db_fields))
			{
				$this->ci->db->order_by($this->db_fields[$sort_by], $sort_order); 
			}
			else
			{
				if ($this->sort_on_rank){
					//default sort on rank	
					$this->ci->db->order_by('rank', $sort_order='desc');
				}
			}			
		}
		
		//select columns for output
		$this->ci->db->select($this->select_fields, FALSE);
		
		//set Limit clause
	  	$this->ci->db->limit($limit, $offset);
        
		//execute search query
		$query= $this->ci->db->get();
		
		//echo $this->ci->db->last_query();
		
		if ($query)
		{
			$result=$query->result_array();
			
			//get total search result count
			$this->search_found_rows=$this->build_search_count($limit,$offset,$filter,$sort_by,$sort_order,$published,$repositoryid);			

			//TODO: instead of fetching authors and survey counts per survey, may be fetch them as array to reduce db queries
			
			//find authors for citations
			foreach($result as $key=>$row)
			{
				$result[$key]['authors']=$this->get_citation_authors($row['id'],'author');
				$result[$key]['survey_count']=$this->get_citation_surveys_count($row['id']);
			}
			
			return $result;
		}				
		return FALSE;
    }
	
	
	//run search count query
    function build_search_count($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL,$published=NULL,$repositoryid=NULL)
    {
		//build where
		$this->build_search_options($limit,$offset,$filter,$sort_by,$sort_order,$published,$repositoryid);
		
		//select columns for output
		$this->ci->db->select('count(citations.id) as rows_found', FALSE);
		        
		//execute search query
		$query= $this->ci->db->get();
		
		//echo $this->ci->db->last_query();
		
		if ($query)
		{
			$result=$query->row_array();
			return $result['rows_found'];
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
	
	//get survey count for each citation
	function get_citation_surveys_count($citationid)
	{
		$this->ci->db->select('count(sid) as total'); 
		$this->ci->db->where('citationid', $citationid); 
		$query=$this->ci->db->get("survey_citations")->row_array();
		
		if($query)
		{
			return $query['total'];
		}
		
		return FALSE;
	}

}// END Search class

/* End of file Citation_search_mysql.php */
/* Location: ./application/libraries/Citation_search_mysql.php */