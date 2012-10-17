<?php
class Form_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
  	
	//return single form row
	function get_single($formid){
		$this->db->where('formid', $formid); 
		return $this->db->get('forms')->row_array();
	}
	
	//return all forms
	function get_all(){
		return $this->db->get('forms')->result_array();
	}

	/**
	*
	* Returns array of form id/name values
	*
	*/
	function get_form_list(){
		$this->db->select('forms.*');
		$this->db->order_by("formid", "asc"); 
		$query=$this->db->get('forms')->result_array();
		
		if($query)
		{
			$result[0]='--Data not accessible to users--';
			foreach($query as $row)
			{
				$result[$row['formid']]=$row['fname'];
			}
			return $result;
		}
		
		return FALSE;
	}
	
	/*
	* Returns the form by survey id
	*
	*/
	function get_form_by_survey($survey_id)
	{
		$this->db->select('forms.*');
		$this->db->from('forms');
		$this->db->join('surveys', 'forms.formid = surveys.formid');
		$this->db->where('id', $survey_id); 
		$query = $this->db->get()->row_array();
		
		return $query;		
	}
	
	/**
	* Insert public request in DB
	*
	*
	*/
	function insert_public_request($survey_id,$user_id,$data_use)
	{
		$data = array(
               'surveyid' => $survey_id ,
               'userid' => $user_id ,
               'abstract' => $data_use,
			   'posted' => date("U")
            );
		
		$result=$this->db->insert('public_requests', $data); 
		log_message('info',"Request received for [Public Use Files]");	
		
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