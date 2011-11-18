<?php
class Block_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
  	
	//return single form row
	function get_single($id){
		$this->db->where('bid', $id); 
		return $this->db->get('blocks')->row_array();
	}
	
	//return all forms
	function get_all(){
		return $this->db->get('blocks')->result_array();
	}


	/**
	*
	* Update block
	*
	*/
	function update($bid, $options)
	{			
		//allowed fields
		$valid_fields=array(
						'block_name',
						'title',
						'body',
						'region',
						'weight',
						'published',
						'pages',
						'block_format'
						);

		$data=array();
		
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		//update
		$this->db->where('bid',$bid);
		$result=$this->db->update('blocks', $data); 

		if ($result===false)
		{
			throw new MY_Exception($this->db->_error_message());
		}
		
		return TRUE;
	}
}
?>