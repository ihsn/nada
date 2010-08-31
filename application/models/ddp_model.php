<?php
class DDP_model extends Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	function test()
	{
		$this->db->limit(0,10);
		return $this->db->get('tb_applications')->result_array();
	}	
  	
	function get_db()
	{
		return $this->db;
	}
	
	//return all surveys
	function get_all_surveys($formid){
		return $this->db->get('surveys')->result_array();
	}
	
	//return all variables per survey
	function get_survey_variables($survey_id){
		$this->db->where('surveyid_fk', $survey_id);
		return $this->db->get('variables')->result_array();
	}
		
}	