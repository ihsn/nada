<?php
class Vocabulary_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* Returns a list of all vocabularies
	*
	*/
	function select_all()
	{		
		$this->db->select('*');
		$this->db->from('vocabularies');
		$query = $this->db->get()->result_array();		
		return $query;
	}

	/**
	*
	* Return a single vocabulary
	*/
	function select_single($vocabularyid)
	{		
		$this->db->select('*');
		$this->db->from('vocabularies');
		$this->db->where('vid', $vocabularyid);
		$query = $this->db->get()->row_array();		
		return $query;
	}
	
	/**
	* 	Create new vocabulary
	*
	*	@title	string
	*/
	function insert($title)
	{			
		$options['title']=$title;
		$result=$this->db->insert('vocabularies', $options); 
				
		if ($result===FALSE)
		{
			throw new MY_Exception($this->db->_error_message());
		}			
		return $this->db->insert_id();
	}

		
	/**
	*
	* Update vocabulary
	*
	*/
	function update($vid, $title)
	{			
		$options['title']=$title;
		$this->db->where('vid',$vid);
		$result=$this->db->update('vocabularies', $options); 
				
		if ($result===false)
		{
			throw new MY_Exception($this->db->_error_message());
		}			
		return TRUE;
	}

	/**
	*
	* Delete a vocabulary and its terms
	*/
	function delete($vid)
	{
		//delete terms
		$this->db->where('vid', $vid);
		$result=$this->db->delete('terms');

		if (!$result)
		{
			return FALSE;
		}
		
		$this->db->where('vid', $vid);
		return $this->db->delete('vocabularies');
	}

	/**
	*
	* Find vocabulary by title
	*/
	function get_vocabulary_by_title($title)
	{		
		$this->db->select('*');
		$this->db->from('vocabularies');
		$this->db->where('title', $title);
		$query = $this->db->get();
		if(!$query)
		{
			return FALSE;
		}
		
		$output=$query->row_array();
		
		return $output;
	}
	
}