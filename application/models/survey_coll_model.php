<?php
/**
* Survey Collections
*
**/
class Survey_coll_model extends CI_Model {
	
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	
	/**
	*
	* detach collection
	**/
	function detach($sid,$tid)
	{
		$options=array(
				'sid'=>$sid,
				'tid'=>$tid
		);
			
		return $this->db->delete("survey_collections",$options);
	}

	/**
	*
	* attach collection to a study
	**/
	function attach($sid,$tid)
	{
		$options=array(
				'sid'=>$sid,
				'tid'=>$tid
		);
		
		return $this->db->insert("survey_collections",$options);
	}
	
	
	

}
?>