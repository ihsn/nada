<?php
class Configurations_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* load nada configurations
	*
	*/
    function load()
    {
		$this->db->select('name,value');
		$result= $this->db->get('configurations');

		if ($result)
		{
			return $result->result_array();
		}		

		return FALSE;	
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
	
	
	/**
	* check if a config key exists
	*
	*/
	function check_key_exists($key)
    {
		$this->db->select('count(*) as found');
		$this->db->where('name',$key);
        $result=$this->db->get('configurations');
		
		if (!$result)
		{
			return FALSE;
		}
		
		$result=$result->row_array();
		
		if ($result && $result['found']>0)
		{
			return TRUE;
		}
		
		return FALSE;
    }	

	/**
	* returns an array of site configurations
	*
	*/	
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
	

	/**
	* 
	* Get a single config value
	*
	*/
	function get_config_item($key)
    {
		$this->db->select('value');
		$this->db->where('name',$key);
        $result=$this->db->get('configurations')->row_array();
		
		if ($result){
			return $result['value'];
		}
		
		return NULL;
    }


	/**
	 * Site setting: enable data classifications (access policy UI + search facets).
	 * Stored as yes/no in configurations; defaults to enabled when the key is absent.
	 *
	 * @return bool
	 */
	public function is_data_classifications_enabled()
	{
		$raw = $this->get_config_item('data_classifications_enabled');
		if ($raw === null || $raw === '')
		{
			return true;
		}

		$v = strtolower(trim((string) $raw));
		if (in_array($v, array('no', '0', 'false', 'off', 'n'), true))
		{
			return false;
		}
		if (in_array($v, array('yes', '1', 'true', 'on', 'y'), true))
		{
			return true;
		}

		return (bool) $raw;
	}


	/**
	* update configurations
	*
	*/
	function update($options)
	{
		foreach ($options as $key => $value)
		{
			if (!$this->upsert($key, $value))
			{
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Remove a configuration row by key name.
	 *
	 * @param string $name
	 * @return bool TRUE if a row was deleted or delete ran without DB error
	 */
	function delete_by_name($name)
	{
		if (trim((string) $name) === '')
		{
			return FALSE;
		}

		$this->db->where('name', $name);

		return $this->db->delete('configurations') === TRUE;
	}
	
	/**
	* add new configuration
	*
	*/
	function add($name, $value,$label=NULL, $helptext=NULL)
	{	
		if (trim($name)=='')
		{
			return FALSE;
		}	

		//check key already exists
		if ($this->check_key_exists($name))
		{
			return FALSE;
		}

		$data=array('name'=>$name,'value'=>$value,'label'=>$label,'helptext'=>$helptext);
		$result=$this->db->insert('configurations', $data);
		
		if(!$result)
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	*
	* Return an array of vocabularies
	*
	*/
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

	
	function upsert($name, $value,$label=NULL, $helptext=NULL)
	{	
		if (trim($name)==''){
			return FALSE;
		}
		
		$data=array(
			'name'=>$name,
			'value'=>$value,
			'label'=>$label,
			'helptext'=>$helptext
		);

		if ($this->check_key_exists($name)){
			$this->db->where('name',$name);
			return $this->db->update('configurations',$data);
		}
		
		return $this->db->insert('configurations', $data);
	}
	
}
?>