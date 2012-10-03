<?php
class Slideshow_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }


	function get_slides($limit=5)
	{
		$this->db->select("*");
		$this->db->order_by("weight"); 
		$this->db->limit($limit);
		$query=$this->db->get("slideshow");

		if ($query)
		{
			return $query->result_array();
		}	
		return FALSE;
	}

	
}