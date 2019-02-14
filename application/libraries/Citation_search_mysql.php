<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Citation Search Class for MySQL
 * 
 * 
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
                        citations.uuid,
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

        $where=array();//all where statements are combined in the end as a custom where to overcome the AR limits

        $this->ci->db->from('citations');
        //$this->ci->db->join('survey_citations', 'survey_citations.citationid = citations.id','left');
        //$this->ci->db->join('surveys', 'survey_citations.sid = surveys.id','left');

        //user created the citation
        $this->ci->db->join('users user_created', 'citations.created_by = user_created.id','left');

        //user changed citation
        $this->ci->db->join('users user_changed', 'citations.changed_by = user_changed.id','left');

        //filter by repository if set
        if($repositoryid!=NULL && strtolower($repositoryid)!='central'){
            //$this->ci->db->join('survey_citations', 'survey_citations.citationid = citations.id','left');
            //$this->ci->db->join('surveys', 'survey_citations.sid = surveys.id','left');
            //$this->ci->db->join('survey_repos', 'surveys.id = survey_repos.sid','inner');
            //$this->ci->db->where('survey_repos.repositoryid',$repositoryid);

            $subquery_repo='citations.id in(select citationid from survey_citations sc
                                inner join surveys on sc.sid=surveys.id 
                                inner join survey_repos sr on sr.sid=surveys.id
                                where sr.repositoryid='.$this->ci->db->escape($repositoryid).')';
            $this->ci->db->where($subquery_repo);
        }

        //$this->ci->db->group_by('citations.id');

        $fulltext_index='citations.title,citations.subtitle,citations.authors,citations.doi,citations.keywords,citations.abstract,citations.notes,citations.organization';
        $country_fulltext_index='surveys.nation';

        if (is_numeric($published)){
            $this->ci->db->where ('citations.published',$published);
        }

        $sort_on_rank=false;

        //set where
        if ($filter)
        {
            foreach($filter as $search_field=>$keywords)
            {
                //echo $search_field;

                switch($search_field)
                {
                    case 'keywords':
                        if (trim($keywords)==""){break;}

                        $keywords_where="(";
                        $keywords_where.=sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)',$fulltext_index,$this->ci->db->escape($keywords));
                        //$keywords_where.=" OR ".sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)',$country_fulltext_index,$this->ci->db->escape($keywords));
                        $keywords_where.=")";

                        //$this->ci->db->where(sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)',$fulltext_index,$this->ci->db->escape($keywords)));
                        //$this->ci->db->or_where(sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)',$country_fulltext_index,$this->ci->db->escape($keywords)));
                        $this->ci->db->where($keywords_where, NUll,FALSE);

                        $this->ci->db->select(sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE) as rank',$fulltext_index,$this->ci->db->escape($keywords)),false);
                        $sort_on_rank=true;

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

                    case 'published':
                        if (is_numeric($keywords)){
                            $this->ci->db->where ('citations.published',$keywords);
                        }
                        break;

                    case 'flag':
                        if ($keywords!=""){
                            $this->ci->db->where_in ('citations.flag',$keywords);
                        }
                        break;

                    case 'user':
                        if (is_array($keywords) && count($keywords)>0){
                            $this->ci->db->where_in('changed_by',$keywords);
                        }
                        break;

                    case 'has_notes':
                        if ($keywords!=""){
                            $this->ci->db->where(" (notes IS NOT NULL and notes !='') ",NULL, FALSE);
                        }
                        break;

                    case 'no_survey_attached': //TODO: broken
                        if ($keywords!=""){
                            //$this->ci->db->having("count(survey_citations.sid)<1",NULL,FALSE);
                        }
                        break;

                    case 'url_status':
                        if ($keywords!=""){
                            $this->ci->db->where_in ('citations.url_status',$keywords);
                        }
                        break;
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
                if ($sort_on_rank){
                    //default sort on rank
                    $this->ci->db->order_by('rank', $sort_order='desc');
                }
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
