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
		$this->db->select('p.*,s.titl,s.surveyid,s.proddate, s.nation, u.username,u.email,m.first_name, m.last_name,m.company, m.phone,m.country',FALSE);
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
		$this->db->select('s.id,s.titl,s.nation,s.data_coll_start,s.data_coll_end');
		$this->db->join('forms', 's.formid = forms.formid','left');
		$this->db->where('forms.model','public');
		return $this->db->get('surveys s')->result_array();
	}


	/**
	* Get a list of PUF surveys by Collection
	**/
	function get_surveys_by_collection($repositoryid)
	{
		$this->db->select('s.id,s.titl,s.nation,s.data_coll_start,s.data_coll_end,forms.model');
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
		$this->db->select('s.id');		
		$this->db->from('public_requests pr');
		$this->db->join('surveys s', 's.id= pr.surveyid','inner');
		$this->db->join('survey_repos sr', 'sr.sid = s.id','inner');		
		$this->db->where('sr.repositoryid',$repositoryid);
		$this->db->where('pr.userid',$user_id);
		
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
	
}
?>