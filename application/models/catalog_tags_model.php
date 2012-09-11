<?php
class Catalog_tags_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	
	
	/**
	* add tag
	*
	* 	options			array
	**/
	function insert($sid,$tag)
	{	
		$options=array(
					'sid'=>$sid,
					'tag'=>$tag);
		
		$result=$this->db->insert('survey_tags', $data); 		
		return $result;		
	}
	
	
	/**
	* checks if a tag exists for the survey
	*
	*/
	function tag_exists($sid,$tag)
	{
		$this->db->select('sid');		
		$this->db->from('survey_tags');		
		$this->db->where('sid',$sid);
		$this->db->where('tag',$tag);				
        return $this->db->count_all_results();
	}
	

	
	function delete($sid,$tag)
	{
		$this->db->where('tag', $tag); 
		$this->db->where('sid',$sid);
		return $this->db->delete('survey_tags');
	}


	//reurns tags associated with a survey
	function survey_tags($sid)
	{
		$this->db->where('sid',$sid);
		return $this->db->get('survey_tags')->result_aray();
	}
	
		

}
?>