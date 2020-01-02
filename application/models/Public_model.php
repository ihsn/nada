<?php
class Public_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* Returns single request info by request ID
	*
	*/
	function select_single($request_id)
	{
		$this->db->select('p.*,s.title,s.idno,s.year_start, s.nation, u.username,u.email,m.first_name, m.last_name,m.company, m.phone,m.country',FALSE);
		$this->db->join('surveys s', 's.id = p.surveyid');
		$this->db->join('users u', 'u.id = p.userid','left');
		$this->db->join('meta m', 'u.id = m.user_id','left');
		$this->db->from('public_requests p');
		$this->db->where('p.id',$request_id);
		
		$result=$this->db->get()->row_array();		
		
		if ($result)
		{
			return $result;
		}
		
		return FALSE;
	}
	

	/**
	*
	* Return all public use surveys
	*
	**/
	function get_all_public_use_surveys()
	{
		$this->db->select('s.id,s.title,s.nation,s.year_start,s.year_end');
		$this->db->join('forms', 's.formid = forms.formid','left');
		$this->db->where('forms.model','public');
		return $this->db->get('surveys s')->result_array();
	}


	/**
	* Get a list of PUF surveys by Collection
	**/
	function get_surveys_by_collection($repositoryid)
	{
		$this->db->select('s.id,s.title,s.nation,s.year_start,s.year_end,forms.model');
		$this->db->from('surveys s');
		$this->db->join('survey_repos repos', 's.id = repos.sid','left');
		$this->db->join('forms', 'forms.formid = s.formid','inner');
		$this->db->where('repos.repositoryid',$repositoryid);
		$this->db->where('forms.model','public');
		return $this->db->get()->result_array();
	}

	
	/**
	* Check if user has already posted a request for public use for a
	* survey in the collection
	*
	**/	
	function check_user_public_request_by_collection($user_id,$repositoryid)
	{
		//get
		$this->db->select('id');		
		$this->db->from('public_requests pr');
		$this->db->where('collectionid',$repositoryid);
		$this->db->where('userid',$user_id);
		
        $result= $this->db->count_all_results();
		
		return $result;
	}
	
	/**
	* Check if user has already posted a request for public use
	*
	*
	**/	
	function check_user_public_request($user_id,$survey_id)
	{
		$this->db->select('id');		
		$this->db->from('public_requests');		
		$this->db->where('surveyid',$survey_id);		
		$this->db->where('userid',$user_id);		
		
        $result= $this->db->count_all_results();
		return $result;
	}
	
	/**
	*
	* Check if user has access to the study or collection to download files
	**/
	function check_user_has_data_access($user_id,$survey_id)
	{
		//single study public requests
		$request_exists=$this->check_user_public_request($user_id,$survey_id);
		
		//get survey collections with GROUP DA option
		$survey_collections=$this->Repository_model->survey_has_da_by_collection($survey_id);

		if ($request_exists)
		{
			return TRUE;
		}
		
		if (!is_array($survey_collections))
		{
			return FALSE;
		}
		
		foreach($survey_collections as $collection)
		{
			//check if user has access to collection's data
			$collection_access=$this->check_user_public_request_by_collection($user_id,$collection['repositoryid']);
			
			if($collection_access)
			{
				return TRUE;
			}
		}

		return FALSE;
	}
	
	
	/**
	* Insert public request in DB
	*/
	function insert_collection_request($collection_id,$user_id,$data_use)
	{
		$data = array(
               'collectionid' => $collection_id,
               'userid' => $user_id ,
               'abstract' => $data_use,
			   'request_type'=>'collection',
			   'posted' => date("U")
            );
		
		$result=$this->db->insert('public_requests', $data); 
		log_message('info',"Request received for [Public Use Files]");	
		
		return $result;
	}
	
}
?>