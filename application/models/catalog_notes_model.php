<?php
/**
* Catalog Notes
*
**/
class Catalog_Notes_model extends CI_Model {
    
	private $fields=array(
		'sid',
		'type',
		'userid',
		'note',
		'changed',
		'created'
	);
	
	public function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data) 
	{
		$options=array();		
		foreach($data as $key=>$value)
		{
			if(in_array($key,$this->fields))
			{
				$options[$key]=$value;
			}
		}
		
		$result = $this->db->insert('survey_notes', $options);
		return $result;
	}
	
	public function update($id,$data) 
	{
		$options=array();
		foreach($data as $key=>$value)
		{
			if(in_array($key,$this->fields))
			{
				$options[$key]=$value;
			}
		}
		
		$this->db->where('id',$id);
		$result = $this->db->update('survey_notes', $options);
		return $result;
	}
	
	public function delete($id) {
		$this->db->where('id', $id); 
		return $this->db->delete('survey_notes');
	}
	
	public function single($id) {
		$this->db->select("*");
		$this->db->where('id', $id); 
		return $this->db->get('survey_notes')->row_array();
	}
	
	//todo: remove
	public function notes_from_catelog_id($sid, $type='admin') {
		$this->db->select("*");
		$this->db->where('type', $type);
		$this->db->where('sid', $sid);
		$this->db->order_by('created', 'DESC');
		return $this->db->get('survey_notes')->result_array();
	}
	
	public function get_notes_by_study($sid,$type=NULL) {
		$this->db->select("*");
		$this->db->where('sid', $sid);
		if ($type)
		{
			$this->db->where('type', $type);
		}	
		$this->db->order_by('type', 'ASC');
		$this->db->order_by('created', 'DESC');
		return $this->db->get('survey_notes')->result_array();
	}
	
	//check if user is the owner of the note
	public function is_note_owner($note_id, $user_id)
	{
		$this->db->select("count(*) as found");
		$this->db->where('userid', $user_id);
		$this->db->where('id', $note_id);
		$result=$this->db->get('survey_notes')->row_array();
		
		if($result['found']>0)
		{
			return TRUE;
		}
		
		return FALSE;
	}

	
}
	