<?php
class Configurations_model extends Model {
 
    public function __construct()
    {
        // model constructor
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	//load nada configurations
    function load()
    {
		//select columns for output
		$this->db->select('name,value');
		
		$result= $this->db->get('configurations');
		
		if ($result)
		{
			return $result->result_array();
		}		

		return $result;
		
		foreach($result as $row){
			$this->config->set_item($row['name'], $row['value']);
		}
    }
  	
	/**
	* returns all settings
	*
	*/
	function select_all()
    {
		$this->db->select('*');		
		$this->db->from('configurations');
        return $this->db->get()->result_array();
    }	

	function get_config_array()
    {
		$this->db->select('name,value');		
		$this->db->from('configurations');
        $rows=$this->db->get()->result_array();
		
		$result=array();
		foreach($rows as $row)
		{
			$result[$row['name']]=$row['value'];
		}
		
		return $result;
    }	

	function update($options)
	{
		
		foreach($options as $key=>$value)
		{
			$data=array('value'=>$value);
			$this->db->where('name', $key);
			$this->db->update('configurations', $data);
		}		
	}
	
	/**
	*
	* Return an array of vocabularies
	*
	**/
	function get_vocabularies_array()
    {
		$this->db->select('vid,title');		
		$this->db->from('vocabularies');
        $query=$this->db->get();
		
		if($query)
		{
			$rows=$query->result_array();
			
			$result=array('-'=>'---');
			foreach($rows as $row)
			{
				$result[$row['vid']]=$row['title'];
			}
			return $result;
		}
		
		return FALSE;
    }	
	
	
}
?>