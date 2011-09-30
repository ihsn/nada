<?php
class Packager_model extends CI_Model {
 
    public function __construct()
    {	
        parent::__construct();		
		//$this->output->enable_profiler(TRUE);
    }

	//returns all surveys
	public function get_surveys()
	{
		$fields='id,repositoryid,surveyid,titl,varcount,ddifilename,dirpath,link_technical, 
				link_study, link_report, link_indicator, ddi_sh, formid, isshared, isdeleted, 
				link_questionnaire, link_da';
		$this->db->select($fields);
		return $this->db->get("surveys")->result_array();		
	}
	
	//get external resources by survey id	
	public function get_resources($id)
	{
		$this->db->select("*");
		$this->db->where('survey_id',$id);
		return $this->db->get("resources")->result_array();
	}
	
	
	//check if study exists by ddi id 
	public function study_exists($surveyid)
	{
		$this->db->select("id");
		$this->db->where('surveyid',$surveyid);
		$result=$this->db->get("surveys")->result_array();
		
		if ($result)
		{
			return $result[0]['id'];
		}
		
		return FALSE;
	}
	
	//update study options
	public function update_study_options($survey_obj)
	{
		//get survey id
		$id=$this->study_exists($survey_obj->surveyid);
		
		if(!$id)
		{
			return FALSE;
		}	
		
		//data to be updated
		$data['link_technical']=$survey_obj->link_technical;
		$data['link_study']=$survey_obj->link_study;
		$data['link_report']=$survey_obj->link_report;
		$data['link_indicator']=$survey_obj->link_indicator;
		$data['formid']=$survey_obj->formid;
		$data['link_questionnaire']=$survey_obj->link_questionnaire;
		$data['link_da']=$survey_obj->link_da;
	
		$this->db->where('id', $id);
		return $this->db->update("surveys", $data);	
	}
	
	
	//import external resources
	public function import_resources($surveyid,$resources_array)
	{
		//check if survey exists
		$id=$this->study_exists($surveyid);
		
		if (!$id)
		{
			return FALSE;
		}
		
		//validate resources
		if (!is_array($resources_array))
		{
			return FALSE;
		}
		
		//import resources
		foreach($resources_array as $resource)
		{
			$resource=(array)$resource;
			//remove columns not needed for insert
			unset($resource['resource_id']);
			
			//set correct surveyid
			$resource['survey_id']=$id;
			
			//insert resource			
			$result=$this->db->insert('resources', $resource); 				
		}
	}
	
}
?>