<?php
class Datafiles_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* get all data files by survey id
	*
	*/
	function get_files($surveyid)
	{		
		$this->db->select('*');
		$this->db->from('data_files');
		$this->db->where('surveyid', $surveyid);
		$query = $this->db->get()->result_array();		
		return $query;
	}

	/**
	*
	* Return a single row by file id
	*/
	function select_single($fileid)
	{		
		$this->db->select('*');
		$this->db->from('data_files');
		$this->db->where('id', $fileid);
		$query = $this->db->get()->row_array();		
		return $query;
	}
	
	/**
	* Add files to a survey
	*
	* 	@surveyid	int
	*	@files		array of file path
	*/
	function insert($surveyid,$options)
	{			
		$options['changed']=date("U");
		$options['surveyid']=$surveyid;
		$result=$this->db->insert('data_files', $options); 
				
		if ($result===false)
		{
			throw new MY_Exception($this->db->_error_message());
		}			
		return TRUE;
	}

	function update($surveyid,$fileid, $options)
	{			
		$options['changed']=date("U");
		$options['surveyid']=$surveyid;
		$this->db->where('id',$fileid);
		$result=$this->db->update('data_files', $options); 
				
		if ($result===false)
		{
			throw new MY_Exception($this->db->_error_message());
		}			
		return TRUE;
	}

	function delete($id)
	{
		$this->db->where('id', $id); 
		return $this->db->delete('data_files');
	}

	/*
	*
	* Update the Request form for the survey
	*/
	function update_form($surveyid,$formid)
	{
		$this->db->where('id', $surveyid); 
		return $this->db->update('surveys',array('formid'=>$formid) );		
	}

	/**
	* Return the access request form info applied to the survey
	*
	*/
	function get_survey_access_form($surveyid)
	{
		$this->db->select('formid');
		$this->db->where('id', $surveyid); 
		$query=$this->db->get('surveys')->row_array();
		
		if(!$query)
		{
			throw new MY_Exception($this->db->last_query());
		}

		$output['formid']=$query['formid'];

		if (!is_numeric($output['formid']) || $output['formid']<1)
		{
			return FALSE;
		}		
		
		//get form model info
		$this->db->select('model');
		$this->db->where('formid', $output['formid']); 
		$query=$this->db->get('forms')->row_array();

		if(!$query)
		{
			throw new MY_Exception($this->db->_error_message());
		}

		$output['model']=$query['model'];
		return $output;				
	}
	
}