<?php
class Repository_model extends CI_Model {
 
    public function __construct()
    {
		$this->load->library("form_validation");
		$this->load->model("Dataset_model");
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	//search
    function search($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL)
    {
		//$this->db->start_cache();

		//select columns for output
		$this->db->select('*');
		
		//allowed_fields
		$db_fields=array('repositoryid', 'title','url','organization');
		
		//set where
		if ($filter)
		{			
			foreach($filter as $f)
			{
				//search only in the allowed fields
				if (in_array($f['field'],$db_fields))
				{
					$this->db->like($f['field'], $f['keywords']); 
				}
				else if ($f['field']=='all')
				{
					foreach($db_fields as $field)
					{
						$this->db->or_like($field, $f['keywords']); 
					}
				}
			}
		}

		//set order by
		if ($sort_by!='' && $sort_order!='')
		{
			$this->db->order_by($sort_by, $sort_order); 
		}
		
		//set Limit clause
	  	$this->db->limit($limit, $offset);
		$this->db->from('repositories');
		//$this->db->stop_cache();

        $result= $this->db->get();
		if ($result)
		{			
			$result=$result->result_array();
			return $result;
		}
		else
		{
			return FALSE;
		}	
    }
  	
    function search_count()
    {
          $count= $this->db->count_all_results('repositories');
		  $this->db->flush_cache();
		  return $count;
    }
	
	/**
	* Select a single repository item
	*
	**/
	function select_single($id)
	{
		if (!is_numeric($id)){
			$this->db->where('repositoryid', (string)$id);
		}else{
			$this->db->where('id', $id); 
		}
		return $this->db->get('repositories')->row_array();
	}
	
	
	function update_survey_data_access($repositoryid,$surveyid,$data_access_type)
	{
		//get survey id
		$survey_id=$this->get_survey_id($repositoryid,$surveyid);
		
		//get data acess form id
		$this->db->select("formid");
		$this->db->where('model', $data_access_type); 
		$form_row=$this->db->get('forms')->row_array();
		
		if ($form_row)
		{
			//update survey form id
			$data=array('formid'=>$form_row['formid']);
			$this->db->where('id', $survey_id); 
			$this->db->update("surveys",$data);	
			
			return TRUE;
		}
		
		return FALSE;
	}
	

	//check if a survey exists in the catalog
	function survey_exists($repositoryid,$surveyid)
	{
		$this->db->where('repositoryid', $repositoryid); 
		$this->db->where('surveyid', $surveyid); 
		$rows=$this->db->get('surveys')->result_array();
		
		if (count($rows)>0)
		{
			return TRUE;
		}
		return FALSE;	
	}


	
	function get_survey_id($repositoryid,$surveyid)
	{
		$this->db->select("id");
		$this->db->where('repositoryid', $repositoryid); 
		$this->db->where('surveyid', $surveyid); 
		$rows=$this->db->get('surveys')->result_array();
		
		if (count($rows)>0)
		{
			return $rows[0]['id'];
		}
		return FALSE;
	}
	
	

	/**
	* update 
	*
	*	id			int
	* 	options		array
	**/
	function update($id,$options)
	{
		//allowed fields
		$valid_fields=array(
			'repositoryid',
			'title',
			'url',
			'organization',
			'country',
			'status',
			'changed',
			'short_text',
			'long_text',
			'thumbnail',
			'type',
			'weight',
			'ispublished',
			'section',
			);

		//add date modified
		$options['changed']=date("U");
					
		//pk field name
		$key_field='id';
		
		$data=array();
		
		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}

		//update db
		$this->db->where($key_field, $id);
		$result=$this->db->update('repositories', $data); 

		return $result;		
	}



	/**
	* add 
	*
	* 	options			array
	**/
	function insert($options)
	{
		//allowed fields
		$valid_fields=array(
			'repositoryid',
			'title',
			'url',
			'organization',
			'country',
			'status',
			'changed',
			'short_text',
			'long_text',
			'thumbnail',
			'type',
			'weight',
			'ispublished',
			'section',
			);

		//add date modified
		$options['changed']=date("U");
							
		$data=array();
		
		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		//insert record into db
		$result=$this->db->insert('repositories', $data); 

		if (!$result){
			$error=$this->db->error();
			throw new Exception(implode(", ",$error));			
        }
		
		return $result;	
	}


	/**
	* checks if a URL exists
	*
	*/
	function url_exists($url,$id=NULL)
	{
		$this->db->select('id');		
		$this->db->from('repositories');		
		$this->db->where('url',$url);		
		if ($id!=NULL)
		{
//			$this->db->where('id',$id);		
			$this->db->where('id !=', $id);
		}
        $result= $this->db->count_all_results();
		return $result;
	}
	
	/**
	*
	* Delete a repository
	*
	**/
	function delete($id=NULL)
	{
		if (!is_numeric($id)){
			return FALSE;
		}
		
		$repo=$this->select_single($id);
		
		$this->db->where('id',$id);
		$this->db->delete('repositories');
		
		//remove from survey_repos
		$this->db->where('repositoryid',$repo['repositoryid']);
		$this->db->delete('survey_repos');

		//update surveys
		$this->db->where('repositoryid',$repo['repositoryid']);
		$this->db->update('surveys',array('repositoryid'=>'central'));
	}


	

	/**
	 * 
	 * 
	 * Return a list of repositories
	 * 
	 */
	function list_all()
	{
		$this->db->select('id,repositoryid,title,ispublished');
		$this->db->order_by('title', 'ASC'); 
		$result=$this->db->get('repositories')->result_array();

		if (!$result){
			return array();
		}

		$repos=array();
		foreach($result as $row){
			$repos[$row['repositoryid']]=$row;
		}
	
		return $repos;
	}

	



	/**
	*
	* Returns an array of all repositories
	*	
	**/
	function select_all($published=null)
	{
		$this->db->select('*');
		$this->db->order_by('title', 'ASC'); 

		if($published!==null){
			$this->db->where('ispublished',$published);
		}

		$query=$this->db->get('repositories');

		if (!$query){
			return array();
		}
		
		$result=$query->result_array();
		
		if (!$result){
			return array();
		}

		$repos=array();
		foreach($result as $row){
			$repos[$row['repositoryid']]=$row;
		}
	
		return $repos;
	}


	
	/**
	*
	* Returns an array of all repositories
	*	
	* Note: duplicate function see catalog_model.php
	**/
	function get_repositories($published=FALSE, $system=TRUE,$exclude_central=TRUE)
	{
		$this->db->select('repositories.*,repository_sections.title as section_title, repository_sections.weight as section_weight');

		if ($published==TRUE){
			$this->db->where("repositories.ispublished",1);
		}

		/*if ($system==FALSE){
			//show system repositories
			$this->db->where("repositories.type !=",2);
		}*/		
		
		$this->db->order_by('repository_sections.weight ASC, repositories.weight ASC, repositories.title'); 
		$this->db->join('repository_sections', 'repository_sections.id= repositories.section','left');
		$query=$this->db->get('repositories');

		if (!$query){
			return array();
		}
		
		$result=$query->result_array();
		
		if (!$result){
			return array();
		}

		//create an array, making the repositoryid array key
		$repos=array();
		foreach($result as $row){
			$repos[$row['repositoryid']]=$row;
		}
	
		return $repos;
	}

	
	
	
	function get_repository_by_repositoryid($repositoryid)
	{
		$this->db->select('*');
		$this->db->where('repositoryid',$repositoryid);
		$query=$this->db->get('repositories');

		if (!$query)
		{
			return FALSE;
		}
		
		return $query->row_array();
	}
	
	
	/**
	*
	* Checks if studies in the repository has citations
	**/
	function has_citations($repositoryid)
	{
		$this->load->library('cache');
		
		//check cache
		$count= $this->cache->get( md5('repository-has-citations'.$repositoryid));
		
		//no cache found
		if ($count===FALSE)
		{					
			$this->db->select('count(survey_repos.sid) as found');
			$this->db->join('survey_repos', 'survey_repos.sid = survey_citations.sid','inner');
			$this->db->where('survey_repos.repositoryid', $repositoryid); 
			$query=$this->db->get("survey_citations")->row_array();
			
			if (count($query)>0)
			{
				$count=$query['found'];
			}
		
			//write cache data
			$this->cache->write($count, md5('repository-has-citations'.$repositoryid));
		}
			
		return $count;
	}

	/**
	*
	* Returns an array of repository sections
	**/
	function get_repository_sections()
	{
		$this->db->order_by('weight','ASC');
		$result= $this->db->get('repository_sections')->result_array();

	/*	$list=array();
		foreach($result as $row)
		{
			$list[$row['title']]=$row['title'];
		} 
		return $list; */
		
		return $result;
	}


	/**
	*
	* Check if survey's data access is set by collection
	*
	* Returns collection id or false
	**/
	function survey_has_da_by_collection($sid)
	{
		$this->db->select('repositories.repositoryid,group_da_public,group_da_licensed,repositories.title');
		$this->db->from('repositories');
		$this->db->join('survey_repos sr', 'sr.repositoryid = repositories.repositoryid','left');		
		$this->db->where('sid',$sid);
		$this->db->where('(group_da_public=1 OR group_da_licensed=1)',NULL,FALSE);
		$result=$this->db->get()->result_array();

		if (!$result)
		{
			return FALSE;
		}

		return $result;
	}
	
	
	/**
	* 
	* Get survey repository info by survey id
	**/
	function get_survey_repositories($survey_id_array)
	{
		$survey_id_array=(array)$survey_id_array;
		
		if (count($survey_id_array)==0)
		{
			return FALSE;
		}
		
		$this->db->select('*');
		$this->db->where_in('sid',$survey_id_array);
		$query=$this->db->get('survey_repos')->result_array();
		
		$output=NULL;
		foreach($query as $row)
		{
			//survey can belong to one or more repos
			$output[$row['sid']][]=$row;
		}
		
		return $output;
	}
	
	/**
	 * 
	 * get repository list by survey id
	 * 
	 * @exclude_owner - exclude repo that own the survey
	 **/ 
	function get_repo_list_by_survey($sid,$exclude_owner=false)
	{
		$this->db->select('repositoryid');
		$this->db->where('sid',$sid);

		if($exclude_owner==true){
			$this->db->where('isadmin!=',1,false);
		}
		
		$rows=$this->db->get('survey_repos')->result_array();
		
		$output=array();
		foreach($rows as $row)
		{
			$output[]=$row['repositoryid'];
		}
		return $output;
	}

	
	function is_valid_repo($repo_name)
	{
		if($repo_name=='central'){
			return true;
		}

		if($this->repository_exists($repo_name)){
			return true;
		}

		return false;
	}
	
	/**
	* checks if a repositoryid exists
	*
	*/
	function repository_exists($repositoryid,$id=NULL)
	{
		$this->db->select('id');
		$this->db->from('repositories');		
		$this->db->where('repositoryid',$repositoryid);		
		if ($id!=NULL)
		{
			$this->db->where('id !=', $id);
		}
        $result= $this->db->count_all_results();
		return $result;
	}


	/**
	*
	* Return survey counts per repository
	**/
	function survey_stats_by_repo()
	{
		$result=$this->db->query('select repositoryid,count(sid) as total from survey_repos group by repositoryid')->result_array();
		return $result;
	}
	
	
	/**
	*
	* Survey counts per data access in the repository e.g. PUF, LIC, Direct Downloads, Remote
	**/
	public function repo_survey_counts_by_data_access($repositoryid,$da_types=NULL)
	{
		$this->db->select('count(surveys.formid) as total,surveys.formid,forms.model as da_type');		
		$this->db->join('forms', 'forms.formid = surveys.formid','inner');
		$this->db->join('survey_repos', 'surveys.id = survey_repos.sid','inner');
		$this->db->group_by('surveys.formid, forms.model');
		$this->db->where('survey_repos.repositoryid',$repositoryid);
		$this->db->where('surveys.published',1);
		
		if(is_array($da_types))
		{
			$this->db->where_in('forms.model',$da_types);
		}
		
		return $this->db->get('surveys')->result_array();
	}
	
	/**
	*
	* Get surveys by repository
	*
	*	@data_access_types	array	public, licensed, etc.
	**/
	public function repo_survey_list($repositoryid,$data_access_types=NULL)
	{
		$this->db->select('surveys.id,surveys.title,surveys.nation,surveys.year_start,surveys.year_end,forms.model as da_model,surveys.created,surveys.changed');
		$this->db->join('survey_repos', 'surveys.id = survey_repos.sid','inner');
		$this->db->join('forms', 'surveys.formid = forms.formid','left');
		$this->db->where('survey_repos.repositoryid',$repositoryid);
		if ($data_access_types)
		{
			$this->db->where_in('forms.model',$data_access_types);
		}
		$this->db->where('surveys.published',1);		
		return $this->db->get('surveys')->result_array();
	}
	
	
	/**
	*
	* Get tree structure for repos/sections
	**/
	public function get_repositories_tree()
	{
		//get repository sections
		$this->db->select('*');
		$this->db->order_by('weight');
		$sections=$this->db->get('repository_sections')->result_array();
		
		//find repos by section
		foreach($sections as $key=>$section)
		{
			$children=$this->get_repositories_by_section($section['id']);
			if($children)
			{
			$sections[$key]['children']=$children;
			}
		}
		
		return $sections;
	}
	
	
	/**
	*
	* Get repositories by section id
	**/
	public function get_repositories_by_section($section_id)
	{
		$this->db->select('r.title,r.repositoryid,r.short_text,count(sr.sid) as surveys_found');
		$this->db->join('survey_repos sr', 'r.repositoryid= sr.repositoryid','INNER');
		$this->db->join('surveys', 'surveys.id= sr.sid','INNER');
		$this->db->where('r.ispublished',1);
		$this->db->where('surveys.published',1);
		//$this->db->where('r.pid >',0);
		$this->db->where('r.section',$section_id);
		$this->db->group_by('r.id,r.pid,r.title,r.repositoryid,r.short_text,r.weight');
		$this->db->order_by('r.weight');		
		return $this->db->get('repositories r')->result_array();	
	}
	
	public function get_repositories_with_survey_counts()
	{
		$this->db->select('r.id,r.pid,r.title,r.repositoryid,count(sr.sid) as surveys_found');
		$this->db->join('survey_repos sr', 'r.repositoryid= sr.repositoryid','INNER');
		$this->db->join('surveys', 'sr.sid= surveys.id','INNER');
		$this->db->where('r.ispublished',1);
		$this->db->where('surveys.published',1);
		//$this->db->where('r.pid >',0);
		$this->db->group_by('r.id,r.pid,r.title,r.repositoryid,r.weight');
		$this->db->order_by('r.weight');		
		$query=$this->db->get('repositories r');
		
		if (!$query)
		{
			return FALSE;
		}
		
		return $query->result_array();
	}
	
	
		/**
	*
	* Returns an array of all repository names
	*	
	**/
	function get_repository_array()
	{
		$this->db->select('repositoryid');
		$query=$this->db->get('repositories');

		if (!$query)
		{
			return array();
		}
		
		$result=$query->result_array();
		
		if (!$result)
		{
			return array();
		}

		//create an array, making the repositoryid array key
		$repos=array();
		foreach($result as $row)
		{
			$repos[]=$row['repositoryid'];
		}
	
		return $repos;
	}

	/**
	*
	* Return collection that owns the study
	**/
	function get_survey_owner_repository($id)
	{
		$this->db->select('repositories.*');
		$this->db->join('surveys','surveys.repositoryid=repositories.repositoryid','INNER');
		$this->db->where('surveys.id',$id);
		$row=$this->db->get('repositories')->row_array();
		return $row;
	}
	
	

	/**
	* Delete orphan rows from the survey_repos table
	**/
	private function remove_orphan_entries()
	{
		$this->db->query('delete from survey_repos where sid not in(select id from surveys);');
	}

	/**
	*
	* Returns all sort of stats for the repository
	**/
	function get_summary_stats($repositoryid)
	{
		$this->remove_orphan_entries();
		
		$output=array(
				'owned'			=>	$this->get_stats_by_ownership($repositoryid,1),
				'linked'		=>	$this->get_stats_by_ownership($repositoryid,0),
				'published'		=>	$this->get_stats_by_published($repositoryid,1),
				'unpublished'	=>	$this->get_stats_by_published($repositoryid,0),
				'lic_requests'	=>	$this->get_stats_pending_requests($repositoryid),
				'microdata'		=>	$this->get_stats_by_study_resources($repositoryid,$resource_type='microdata'),
				'questionnaires'	=>	$this->get_stats_by_study_resources($repositoryid,$resource_type='questionnaire'),
				'total_puf'		=>	$this->get_stats_total_PUF_studies($repositoryid)
			);
		
		return $output;	
	}
	
	
	/**
	* Return study counts by owned or linked
	* @ownership	1=owned, 0=linked
	**/
	function get_stats_total_PUF_studies($repositoryid)
	{
		$this->db->select('count(surveys.id) as total');
		$this->db->join('surveys','surveys.id=survey_repos.sid');
		//$this->db->join('forms','surveys.formid=forms.formid');
		$this->db->where('survey_repos.repositoryid',$repositoryid);
		$this->db->where('survey_repos.isadmin',1);
		//$this->db->where_in('forms.model',array('public','direct','licensed'));

		$row=$this->db->get('survey_repos')->row_array();
		return $row['total'];
	}
	
	/**
	* Return study counts by owned or linked
	* @ownership	1=owned, 0=linked
	**/
	function get_stats_by_ownership($repositoryid,$ownership=1)
	{
		if($ownership==0 && $repositoryid=='central')
		{
			$this->db->select('count(surveys.id) as total');
			$row=$this->db->get('surveys')->row_array();
			$total_studies=$row['total'];
			
			return $total_studies - $this->get_stats_by_ownership($repositoryid,$ownership=1);
		}
				
		$this->db->select('count(sid) as total');
		$this->db->where('repositoryid',$repositoryid);
		$this->db->where('isadmin',$ownership);
		$row=$this->db->get('survey_repos')->row_array();
		return $row['total'];
	}


	/**
	* Return study counts by published or unpublished
	* @status	1=published, 0=unpublished
	**/
	function get_stats_by_published($repositoryid,$status=1)
	{
		$this->db->select('count(sid) as total');
		$this->db->join('surveys','surveys.id=survey_repos.sid');
		$this->db->where('survey_repos.repositoryid',$repositoryid);		
		$this->db->where('surveys.published',$status);
		$row=$this->db->get('survey_repos')->row_array();
		return $row['total'];
	}
	
	
	
	/**
	* Return study counts by published or unpublished
	* @status	1=published, 0=unpublished
	**/
	function get_stats_pending_requests($repositoryid)
	{
		$this->db->select('count(distinct lic_requests.id) as total');
		$this->db->join('survey_lic_requests','survey_lic_requests.sid=survey_repos.sid');		
		$this->db->join('lic_requests','survey_lic_requests.request_id=lic_requests.id');
		$this->db->where('repositoryid',$repositoryid);
		$this->db->where('survey_repos.isadmin',1);
		$this->db->where('lic_requests.status','PENDING');
		$row=$this->db->get('survey_repos')->row_array();
		return $row['total'];
	}
	
	/**
	* Return study resource stats by repositoryid
	*
	* $resource_type	microdata or questionnaire
	**/
	function get_stats_by_study_resources($repositoryid,$resource_type='microdata')
	{
		$this->db->select('count(distinct survey_repos.sid) as total');
		$this->db->join('survey_repos','survey_repos.sid=resources.survey_id');
		$this->db->join('surveys','surveys.id=resources.survey_id');
		//$this->db->join('forms','surveys.formid=forms.formid');
		$this->db->where("survey_repos.repositoryid",$repositoryid);
		$this->db->where('survey_repos.isadmin',1);
		//$this->db->where_in('forms.model',array('public','direct','licensed'));

		if($resource_type=='microdata')
		{
			//$this->db->where_in('forms.model',array('public','direct','licensed'));
			$this->db->where(" (dctype like '%dat/micro]%' OR dctype like '%dat]%') ",NULL,FALSE);
		}
		else //if ($resource_type=='questionnaire')
		{
			$this->db->like('dctype','doc/qst]');
		}
	
		$row=$this->db->get('resources')->row_array();
		//echo $this->db->last_query();exit;
		return $row['total'];
	}



	/**
	*
	* Link to a study from another repo
	**/
	function link_study($repositoryid,$sid,$isadmin=0)
	{
		$options=array(
				'repositoryid'=>$repositoryid,
				'sid'=>$sid,
				'isadmin'=>0
			);
		
		//first unlink incase it is already set
		$this->unlink_study($repositoryid,$sid,$isadmin);
		return $this->db->insert("survey_repos",$options);
	}

	/**
	*
	* unlink a study to a repository
	**/
	function unlink_study($repositoryid,$sid,$isadmin=0)
	{
		$options=array(
				'repositoryid'=>$repositoryid,
				'sid'=>$sid,
				'isadmin'=>$isadmin
		);
			
		return $this->db->delete("survey_repos",$options);
	}

	
	/**
	*
	* unlink study from all collections
	*
	**/
	function unlink_study_all($sid)
	{
		$options=array(				
				'sid'=>$sid,
				'isadmin'=>0
		);
			
		return $this->db->delete("survey_repos",$options);
	}


	/**
	*
	* Return array describing central catalog
	**/
	function get_central_catalog_array()
	{
		return 	array(
			'id'			=> 0,
			'repo_id'		=> 0,
			'repositoryid'	=> 'central',
			'title'			=> t('central_data_catalog'),
			'thumbnail'		=> 'files/icon-blank.png',
			'short_text'	=>	t('central_catalog_short_text')
		);
	}
	
	/**
	*
	* Return array of all roles names for collections
	**/
	function get_repo_permission_groups()
	{
		$this->db->select('*');
		$this->db->order_by('weight');
		$output=$this->db->get('repo_perms_groups')->result_array();
		
		return $output;
	
	}


	/**
	*
	* Return an array of linked studies for the collection
	**/
	function get_repo_linked_studies($repositoryid)
	{
		$this->db->select('sid');
		$this->db->where('isadmin',0);
		$this->db->where('repositoryid',$repositoryid);
		$query=$this->db->get('survey_repos');
		
		if (!$query)
		{
			return array();
		}
		
		$result=$query->result_array();
		
		$output=array();
		foreach($result as $row)
		{
			$output[]=$row['sid'];
		}
		
		return $output;
	}


	/**
	*
	* Return an array of collection IDs by study
	*
	**/
	function linked_repos_by_study($sid)
	{
		$this->db->select('repositoryid');
		$this->db->where('isadmin',0);
		$this->db->where('sid',$sid);
		$query=$this->db->get('survey_repos');
		
		if (!$query){
			return array();
		}
		
		$result=$query->result_array();
		
		$output=array();
		foreach($result as $row)
		{
			$output[]=$row['repositoryid'];
		}
		
		return $output;
	}


	/**
	*
	* Return an array of collection IDs by study
	*
	**/
	function linked_repos_by_studies($sid_arr)
	{
		if (empty($sid_arr)){
			return false;
		}

		$this->db->select('repositoryid,sid');
		$this->db->where('isadmin',0);
		$this->db->where_in('sid',$sid_arr);
		$query=$this->db->get('survey_repos');
		
		if (!$query){
			return array();
		}

		$result=$query->result_array();
		
		$output=array();
		foreach($result as $row){
			$output[$row['sid']][]=$row['repositoryid'];
		}
		
		return $output;
	}
	
	/**
	*
	* Return an array of owned studies for the collection
	**/
	function get_repo_owned_studies($repositoryid)
	{
		$this->db->select('sid');
		$this->db->where('isadmin',1);
		$this->db->where('repositoryid',$repositoryid);
		$query=$this->db->get('survey_repos');
		
		if (!$query)
		{
			return array();
		}
		
		$result=$query->result_array();
		
		$output=array();
		foreach($result as $row)
		{
			$output[]=$row['sid'];
		}
		
		return $output;
	}
	
	
	/**
	*
	* Publish/Unpublish all studies in a repository
	**/
	function update_repo_studies_status($repo_id,$status)
	{
		//get repositoryid
		$repositoryid=$this->get_repositoryid_by_uid($repo_id);
				
		//get a list repo studies
		$studies=$this->get_repo_owned_studies($repositoryid);

        //do nothing if no studies found in the collection
        if (count($studies)==0)
        {
            return false;
        }

		$options=array(
            'published'=>0
		);

		$sql=sprintf('UPDATE surveys set published=%d where surveys.id in (%s)',intval($status),implode(",",$studies));
		
		return $this->db->query($sql);
	}

	
	function get_repositoryid_by_uid($repo_id)
	{
		$this->db->select('repositoryid');
		$this->db->where('id',$repo_id);
		$query=$this->db->get('repositories');

		if (!$query)
		{
			return FALSE;
		}
		
		$row=$query->row_array();
		
		return $row['repositoryid'];
	}
	
	function get_repositoryid_uid($repositoryid)
	{
		$this->db->select('id');
		$this->db->where('repositoryid',$repositoryid);
		$query=$this->db->get('repositories');

		if (!$query)
		{
			return FALSE;
		}
		
		$row=$query->row_array();
		
		if ($row)
		{
			return $row['id'];
		}		
	}
	
	//returns number of citations by collection
	function get_citations_count_by_collection($repositoryid=NULL)
	{
		$this->db->select('count(*) as total');
		$this->db->join('survey_repos', 'survey_repos.sid = survey_citations.sid','inner');

		if ($repositoryid!=NULL && $repositoryid!='central'){
			$this->db->where('survey_repos.repositoryid', $repositoryid);
		}

		$result=$this->db->get('survey_citations')->row_array();
		return $result['total'];
	}


	//get featured study by repository
	function get_featured_study($repositoryid=null,$study_type=null)
	{
		$id=0;
		if($repositoryid){
			$id=$this->get_repositoryid_uid($repositoryid);
		}

		$this->db->select('featured_surveys.sid');
		$this->db->join('surveys','surveys.id=featured_surveys.sid','INNER');
		$this->db->where("surveys.published",1);
		$this->db->where("featured_surveys.repoid",$id);
		if($study_type){
			$this->db->where("surveys.type",$study_type);
		}
		$this->db->limit(5);
		$query=$this->db->get('featured_surveys');

		if (!$query){
			return FALSE;
		}
		
		$rows=$query->result_array();
		
		if (!$rows){
			return FALSE;
		}

		$sid_arr=array_values(array_column($rows,'sid'));

		$studies=array();
		foreach($sid_arr as $sid){
			$study=$this->Catalog_model->select_single($sid);
			$study['repo_title']=$this->get_repo_title($study['repositoryid']);
			$studies[]=$study;
		}

		return $studies;
	}

	function get_repo_title($repositoryid)
	{
		$this->db->select('title');
		$this->db->where('repositoryid',$repositoryid);
		$result=$this->db->get('repositories')->row_array();
		
		if ($result){
			return $result['title'];
		}
	}


	//add/remove featured study to a repository
	function set_featured_study($repositoryid,$sid,$status)
	{
		$repo=$this->get_repository_by_repositoryid($repositoryid);
		
		$repo_uid=0;//central

		if ($repo){			
			$repo_uid=$repo['id'];		
		}
		
		$options=array(
			'repoid'	=>	$repo_uid,
			'sid'		=>	$sid,
		);
		
		if($status>0){
			$this->db->insert('featured_surveys',$options);
		}
		else{
			$this->db->where('repoid',$repo_uid);
			$this->db->where('sid',(int)$sid);
			$this->db->delete('featured_surveys');
		}
		
		return true;
	}
	
	
	function is_a_featured_study($repoid,$sid)
	{
		$this->db->select('count(sid) as found');
		$this->db->where('repoid',$repoid);
		$this->db->where('sid',$sid);
		$result=$this->db->get('featured_surveys')->row_array();
		
		if ($result['found']>0)
		{
			return TRUE;
		}
		
		return FALSE;	
	}
	
	function get_all_featured_studies()
	{
		$this->db->select('surveys.id,surveys.titl,repositories.id as repo_id,repositories.repositoryid,surveys.nation,surveys.year_start');
		$this->db->join('repositories','repositories.id=featured_surveys.repoid','INNER');
		$this->db->join('surveys','surveys.id=featured_surveys.sid','INNER');
		$query=$this->db->get('featured_surveys');
		if (!$query)
		{
			return FALSE;
		}
		
		return $query->result_array();
	}


	/**
	 * 
	 * Return a list of all linked or owned studies for a collection
	 * 
	 * @rep_id - repository unique ID
	 * @ispublished - by default all studies are returned regardless of published status
	 * 
	 */
	function get_all_repo_studies($repo_id, $ispublished=null)
	{
		$this->db->select('surveys.id,surveys.idno,survey_repos.repositoryid,surveys.title,surveys.nation,surveys.year_start');
		$this->db->join('survey_repos','survey_repos.sid=surveys.id','INNER');
		$this->db->where('survey_repos.repositoryid',$repo_id);

		if($ispublished!==null){
			$this->db->where('surveys.published',$ispublished);
		}

		$query=$this->db->get('surveys');

		if (!$query)
		{
			return FALSE;
		}
		
		return $query->result_array();

	}


	/**
	* Returns the active repo for the current logged in user
	**/
	function user_active_repo()
	{
		$repoid=$this->input->cookie('active_repo',TRUE);
		
		if (!is_numeric($repoid)){
			return FALSE;
		}

		return (int)$repoid;		
	}
	
	
	/**
	* set active repo for the session
	**/
	function set_active_repo($repoid)
	{
		$this->input->set_cookie($name='active_repo', $value=$repoid, $expire=865000, $domain='', $path='/', $prefix='', $secure=FALSE);
		return TRUE;
	}
	
	/**
	*Clear active repo from user session
	**/
	function clear_active_repo()
	{
		$this->input->set_cookie($name='active_repo', $value='', $expire=0, $domain='', $path='/', $prefix='', $secure=FALSE);
	}

	/**
	 * 
	 * 
	 * Validate repository options
	 * 
	 */
	function validate($options)
	{				
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);
	
		//set validation rules
		$this->form_validation->set_rules('title', t('title'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('short_text', t('short_text'), 'xss_clean|trim|required|max_length[1000]');
		$this->form_validation->set_rules('long_text', t('long_text'), 'trim|xss_clean|required');
		$this->form_validation->set_rules('weight', t('weight'), 'xss_clean|trim|max_length[3]|is_natural');
		$this->form_validation->set_rules('section', t('section'), 'xss_clean|trim|max_length[3]|is_natural');
		$this->form_validation->set_rules('ispublished', t('published'), 'required|xss_clean|trim|max_length[1]|is_natural');			
			
		//repositoryid validation rule
		$this->form_validation->set_rules(
			'repositoryid', 
			t('repositoryid'),
			array(
				"required",
				"alpha_dash",
				"max_length[30]",
				"xss_clean",
				array(
					'validate_repository_id_format_check',
					array($this, 'validate_repository_id_format_check')
				),
				array(
					'validate_repository_id_check',
					array($this, 'validate_repository_id_check')
				)				
			)		
		);

		//file upload
		/*$this->form_validation->set_rules(
			'thumbnail', 
			t('thumbnail'),
			array(						
				array(
					'validate_thumbnail_upload',
					array($this, 'validate_thumbnail_upload')
				),				
			)		
		);*/
		
		if ($this->form_validation->run() == TRUE){
			return TRUE;
		}
		
		//failed
		$errors=$this->form_validation->error_array();
		$error_str=$this->form_validation->error_array_to_string($errors);
		throw new ValidationException("VALIDATION_ERROR: ".$error_str, $errors);
	}

	

	/**
     *
     * The repository ID call back to validate ID is not numeric
     */
    function validate_repository_id_format_check($repositoryid)
    {
        if (is_numeric($repositoryid))
        {
            $this->form_validation->set_message('validate_repository_id_format_check', t('callback_error_repositoryid_is_numeric'));
            return FALSE;
        }
        return TRUE;
	}


	/**
     *
     * Check if repository ID already exists?
     */
	public function validate_repository_id_check($repositoryid)
	{	
		$repo_pk_id=null;
		if(array_key_exists('id',$this->form_validation->validation_data)){
			$repo_pk_id=$this->form_validation->validation_data['id'];
		}

		//check if already exists
		$id=$this->get_repositoryid_uid($repositoryid);	

		if (is_numeric($id) && $id!=$repo_pk_id) {
			$this->form_validation->set_message(__FUNCTION__, 'The ID should be unique.' );
			return false;
		}
		return true;
	}

	/**
	 * 
	 * 
	 * Callback for handling thumbnail upload
	 * 
	 */
	function validate_thumbnail_upload()
	{
		if(!empty($_FILES['thumbnail']['name'])) {
			$thumbnail_storage=$this->get_collection_storage_path();	

			if(!file_exists($thumbnail_storage)){
				$error=t('thumbnail_upload_folder_not_set').': '.$thumbnail_storage;
				$this->form_validation->set_message('validate_thumbnail_upload',$error);
				return false;
			}
			
			$fileupload_output=$this->upload_thumbnail('thumbnail', $thumbnail_storage);
			
			if($fileupload_output['status']=='success'){
				$this->uploaded_thumbnail_path=$fileupload_output['file_name'];
			}
			else{
				$error=t('thumbnail_upload_failed').': '. $fileupload_output['data']['error']. ' upload folder: '.$thumbnail_storage;
				$this->form_validation->set_message('validate_thumbnail_upload', $error);
				return false;
			}
		}		
		return true;
	}
	
	/**
	 * 
	 * Return collections storage path for thumbnails
	 * 
	 */
	function get_collection_storage_path()
	{
		$this->load->config("collections");
		$thumbnail_storage=$this->config->item('collection_image_path');		
		return $thumbnail_storage;
	}

	//process thumbnail uploads
	function upload_thumbnail($file_name, $upload_path=NULL)
	{
		if (!$upload_path){
			$upload_path=$this->get_collection_storage_path();
		}

		if(!file_exists($upload_path)){
			$error=t('thumbnail_upload_folder_not_set').': '.$upload_path;
			throw new exception($error);
		}

		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'gif|jpg|png';
		$config['max_size']	= '300';
		$config['overwrite']=true;
		$config['file_ext_tolower']=true;
		//$config['max_width']  = '300';
		//$config['max_height']  = '300';

		$this->load->library('upload', $config);

		$upload_result=$this->upload->do_upload($file_name);

		if (!$upload_result){
			throw new Exception($this->upload->display_errors());
		}

		$data= $this->upload->data();
		$data['rel_path']=$upload_path. '/' . $data['file_name'];
		return $data;
	}	


	function rename_repository($old_repositoryid, $new_repositoryid)
	{
		$options=array(
			'repositoryid'=>$new_repositoryid
		);

		//rename repositoryid
		$this->db->where("repositoryid",$old_repositoryid);
		$result=$this->db->update("repositories",$options);

		//replace related survey_repos table
		return $this->rename_survey_repos($old_repositoryid, $new_repositoryid);
	}

	function rename_survey_repos($old_repositoryid, $new_repositoryid)
	{
		$options=array(
			'repositoryid'=>$new_repositoryid
		);

		$this->db->where("repositoryid",$old_repositoryid);
		return $this->db->update("survey_repos",$options);
	}

	
	/**
	 * 
	 * Update linked + owner collections
	 * 
	 * $options - array
	 * 		- study_idno - study idno
	 * 		- owner_collection - (optional) owner collection ID
	 * 		- link_collections - list of collection IDs for linking to study
	 * 		- mode - replace or update
	 * 
	 */
	function update_collection_studies($options)
	{
		if(!isset($options['study_idno'])){
			throw new Exception("study_idno is required");
		}

		if(!isset($options['link_collections'])){
			throw new Exception("link_collections is required");
		}

		if(!is_array($options['link_collections'])){
			throw new Exception("link_collections is not an array");
		}

		$study_idno= $options['study_idno'];
		$owner_collection=isset($options['owner_collection']) ? strtolower($options['owner_collection']) : null;
		$link_collections=(array)$options['link_collections'];		
		$mode=isset($options['mode']) && in_array($options['mode'],array('replace','update')) ? $options['mode'] : 'update';

		//get sid
		$sid=$this->Dataset_model->get_id_by_idno($study_idno);

		if(!$sid){
			throw new Exception("STUDY_NOT_FOUND: ".$study_idno);
		}
		
		//check/update owner collection
		if ($owner_collection){
			$owner_collection_id=$this->get_repositoryid_uid($owner_collection);

			if(!$owner_collection_id && $owner_collection!='central'){
				throw new Exception("INVALID_COLLECTION_OWNER: ". $owner_collection);
			}

			//set study collection owner
			$this->Dataset_model->set_dataset_owner_repo($sid,$owner_collection);
		}

		//get all linked collections
		$repos=(array)$this->linked_repos_by_study($sid);

		$link_collections=array_map('strtolower',$link_collections);
		$repos=array_map('strtolower',$repos);

		//validate all linked collections exist
		foreach($link_collections as $collection){			
			if (!$this->get_repositoryid_uid($collection)){
				throw new Exception("INVALID_COLLECTION_ID: ".$collection);
			}
		}

		//update/replace by mode
		if ($mode=='update'){
			$repos=array_merge($repos,$link_collections);
		}
		else if($mode=='replace'){
			$repos=$link_collections;
		}

		if($owner_collection){
			$repos=array_diff($repos,(array)$owner_collection);
		}

		//remove all linked
		$this->unlink_study_all($sid);

		//update linked collections
		foreach($repos as $repo){
			$this->link_study($repo,$sid,$isadmin=0);
		}	
	}


	/**
	 * 
	 * Return all datasets with owner and linked collections
	 * 
	 * @offset - offset
	 * @limit - number of rows to return
	 * 
	 */
	function owner_linked_collections($limit=0,$offset=0,$study_idno=null)
	{
		$this->db->select('surveys.id,idno,surveys.repositoryid as collection_owner,survey_repos.repositoryid as linked_collection');
		$this->db->join('survey_repos', 'surveys.id= survey_repos.sid','left');
		$this->db->order_by('surveys.id');

		if ($limit>0){
			$this->db->limit($limit, $offset);
		}

		if($study_idno){
			$this->db->where("surveys.idno",$study_idno);
		}
		
		return $this->db->get("surveys")->result_array();
	}

}
