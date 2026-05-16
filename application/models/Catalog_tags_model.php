<?php
/**
* Catalog tags
*
**/
class Catalog_tags_model extends CI_Model {
    
	public function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data) 
	{
		if (isset($data['tag'])) {
			$data['tag'] = xss_clean($data['tag']);
		}
		
		$result = $this->db->insert('survey_tags', $data);
		return $result;
	}

	public function upsert($sid,$tag) 
	{
		$tag = xss_clean($tag);
		
		if (empty($tag)) {
			return false;
		}
		
		$tag_exists=$this->tag_exists($sid, $tag);
		
		if (!$tag_exists){

			$options=array(
				'sid'=>$sid,
				'tag'=>$tag
				);
			
			return $this->insert($options);
		}
		
		return false;
	}
	
	public function tag_exists($sid,$tag)
	{
		$this->db->select('sid');		
		$this->db->from('survey_tags');		
		$this->db->where('sid',$sid);
		$this->db->where('tag',$tag);				
        return $this->db->count_all_results();
	}
	
	public function delete($id) 
	{
		$this->db->where('id', $id);
		$this->db->delete('survey_tags');
		return $this->db->affected_rows() > 0;
	}


	/**
	 * Remove a tag row by survey id and normalized tag string.
	 *
	 * @param int    $sid Internal surveys.id
	 * @param string $tag Stored tag value (same normalization as insert/upsert)
	 */
	public function delete_by_sid_and_tag($sid, $tag)
	{
		$tag = xss_clean($tag);
		if ($tag === '') {
			return false;
		}
		$this->db->where('sid', (int) $sid);
		$this->db->where('tag', $tag);
		$this->db->delete('survey_tags');
		return $this->db->affected_rows() > 0;
	}
	
	public function single($id) 
	{
		$this->db->select("*");
		$this->db->where('id', $id); 
		return $this->db->get('survey_tags')->row_array();
	}


	//returns tags associated with a survey
	function survey_tags($sid)
	{
		$this->db->where('sid',$sid);
		return $this->db->get('survey_tags')->result_array();
	}

	function survey_tags_with_key($sid)
	{
		$this->db->select("tag");
		$this->db->where('sid',$sid);
		return $this->db->get('survey_tags')->result_array();
	}

	
	function survey_tags_list($sid)
	{
		$this->db->select("tag");
		$this->db->where('sid',$sid);
		$tags=$this->db->get('survey_tags')->result_array();
		
		if($tags){
			return array_column($tags,'tag');
		}
		
		return array();
	}


	/**
	 * 
	 * Delete all tags for a survey
	 * 
	 */
	function delete_survey_tags($sid)
	{
		$this->db->where('sid',$sid);
		return $this->db->delete('survey_tags');
	}

	
	
	public function tags_from_catelog_id($sid) 
	{
		$this->db->select("*");
		$this->db->where('sid', $sid);
		$this->db->order_by('id', 'DESC');
		return $this->db->get('survey_tags')->result_array();
	}
}
	
