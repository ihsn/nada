<?php

class Data_file_resources_model extends CI_Model {

    public function __construct()
    {
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* get all resources linked to a single file
	*
	*/
	function get_file_resources($sid,$file_id)
	{		
		$this->db->select('data_files_resources.*,resources.filename');
		$this->db->from('data_files_resources');
        $this->db->where('sid', $sid);
		$this->db->where('fid', $file_id);
		$this->db->join('resources','resources.resource_id=data_files_resources.resource_id','left');
		
		$result = $this->db->get()->result_array();

		$output=array();

		foreach($result as $row)
		{
			$output[$row['resource_id']]=$row;
		}

		return $output;
	}

	/**
	* get all files with attached resources info for a survey
	*
	*/
	function get_all_files_resources($sid)
	{	
		/*select 
		* 
		from data_files_resources dfr
		left join resources on resources.resource_id=dfr.resource_id
		left join data_files on data_files.file_id=dfr.fid AND data_files.sid=dfr.sid;
		*/
		
/*
		$this->db->select('dfr.*,resources.filename,data_files.file_name, data_files.description');
		$this->db->from('data_files_resources dfr');
        $this->db->where('dfr.sid', $sid);
		$this->db->join('resources','resources.resource_id=dfr.resource_id','left');
		$this->db->join('data_files','data_files.file_id=dfr.fid AND data_files.sid=dfr.sid','left');
		*/

		/*
		select 
		dfr.*,resources.filename,data_files.file_name, data_files.description,data_files.file_id
		from data_files
		left join data_files_resources dfr on data_files.file_id=dfr.fid AND data_files.sid=dfr.sid
		left join resources on resources.resource_id=dfr.resource_id
		;*/
	

		$this->db->select('dfr.*,resources.filename,data_files.file_name, data_files.description, data_files.file_id, data_files.sid');
		$this->db->from('data_files');
        $this->db->where('data_files.sid', $sid);
		$this->db->join('data_files_resources dfr','data_files.file_id=dfr.fid AND data_files.sid=dfr.sid','left');
		$this->db->join('resources','resources.resource_id=dfr.resource_id','left');
		$result = $this->db->get()->result_array();

		$output=array();

		foreach($result as $row){
			$output[$row['file_id']][]=$row;
		}

		return $output;
	}


	
	function batch_update($sid,$file_id, $options)
	{
		//delete existing 
		$this->delete_all_by_file($sid,$file_id);

		//update
		foreach($options as $option){
			$option['sid']=$sid;
			$options['fid']=$file_id;

			$this->db->insert('data_files_resources',$option);
		}
	}

	function delete_all_by_file($sid,$file_id)
	{
		$this->db->where('sid',$sid);
		$this->db->where('fid',$file_id);
		return $this->db->delete('data_files_resources');
	}

    
}    