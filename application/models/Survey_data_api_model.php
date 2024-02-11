<?php
class survey_data_api_model extends CI_Model {

	/*
	fields:
		- id
		- sid
		- title - dataset title
		- db_id
		- table_id
	*/

	private $fields=array(
		'sid',
		'title',
		'db_id',
		'table_id',
	);

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }


	function select_single($id)
	{
		$this->db->select('*');
		$this->db->from('survey_data_api');
		$this->db->where('id',$id);
		$query=$this->db->get();
		$result=$query->row_array();
		return $result;
	}

	function get_by_sid($sid)
	{
		$this->db->select('*');
		$this->db->where('sid',$sid);
		$result=$this->db->get('survey_data_api')->result_array();

		if($result){
			return $result;
		}		
	}

	function delete($id)
	{
		$this->db->where('id', $id);
		$result=$this->db->delete('survey_data_api'); 

		return $result;
	}


	function delete_by_sid($sid)
	{
		$this->db->where('sid', $sid);
		$result=$this->db->delete('survey_data_api');
		return $result;
	}

	function detach($sid,$db_id,$table_id)
	{
		$this->db->where('sid', $sid);
		$this->db->where('db_id', $db_id);
		$this->db->where('table_id', $table_id);
		$result=$this->db->delete('survey_data_api');
		return $result;
	}

	
	/**
	* update 
	*
	*	id			int
	* 	options		array
	**/
	function update($id,$options)
	{
		$data=array();
		
		foreach($options as $key=>$value){
			if (in_array($key,$this->fields) ){
				$data[$key]=$value;
			}
		}

		$this->db->where('id', $id);
		$result=$this->db->update('survey_data_api', $data); 

		return $result;		
	}



	/**
	* Insert new record
	*
	* @options	array
	**/
	function insert($options)
	{
		if (!isset($options['idno'])){
			throw new Exception("missing: IDNO");
		}

		$sid=$this->get_sid_by_idno($options['idno']);

		if (!$sid){
			throw new Exception("IDNO not found");
		}

		$options['sid']=$sid;

		$data=array();
		foreach($options as $key=>$value){
			if (in_array($key,$this->fields)){
				$data[$key]=$value;
			}
		}

		if ($this->exists($data['sid'],$data['db_id'],$data['table_id'])){
			return true;
		}
		
		$result=$this->db->insert('survey_data_api', $data); 

		if (!$result){
			$error=$this->db->error();
			throw new Exception(implode(", ",$error));			
        }
		
		return $result;	
	}


	/**
	 * 
	 * 
	 * Check if record exists
	 * 
	 * 
	 */
	function exists($sid,$db_id,$table_id)
	{
		$this->db->select('*');
		$this->db->where('sid',$sid);
		$this->db->where('db_id',$db_id);
		$this->db->where('table_id',$table_id);
		$result=$this->db->get('survey_data_api')->result_array();

		if($result){
			return true;
		}		

		return false;
	}


	function get_sid_by_idno($idno)
	{
		$this->db->select("id");
		$this->db->where('idno',$idno);
		$result=$this->db->get('surveys')->row_array();

		if ($result){
			return $result['id'];
		}

		return false;
	}
	
}
	
