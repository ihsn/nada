<?php
class Related_study_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	*
	* add/update relationship
	*
	*/
	public function update($p_sid,$c_sid_arr,$relation_id)
	{	
		foreach($c_sid_arr as $sid_2)
		{
			$options=array(
				'sid_1'				=>	$p_sid,
				'sid_2'				=>	$sid_2,
				'relationship_id'	=>	$relation_id
			);
			
			$result=$this->db->insert('survey_relationships', $options); 
					
			if ($result===false)
			{
				throw new MY_Exception($this->db->_error_message());
			}			
		}
		return TRUE;
	}
	
	/**
	*
	* remove study relationship from both sides
	**/
	public function delete($sid)
	{
	
	
	}
	
	
	
	
	public function get_relationships($sid)
	{
		$this->db->select('surveys.titl, survey_relationships.*');
		$this->db->where('sid_1',$sid);
		$this->db->join('surveys','surveys.id=survey_relationships.sid_2','INNER');
		return $this->db->get('survey_relationships')->result_array();
	}
	
	
	public function get_relationship_types_array()
	{
		//$this->db->select('*');
		//$types=$this->db->get('survey_relationship_types')->result_array();
		
		$sql='select * from (
			select 
				srt1.id,
				srt1.rel_group_id,
				srt1.rel_name,
				srt1.rel_dir,
				srt2.rel_name as rel_name2, 
				srt2.rel_dir as rel_dir2
			from survey_relationship_types srt1
				inner join survey_relationship_types srt2 on srt1.rel_group_id=srt2.rel_group_id
				where srt1.rel_dir=0 and srt2.rel_dir=1
			UNION ALL
			select 
				srt1.id,
				srt1.rel_group_id,
				srt1.rel_name,
				srt1.rel_dir,
				srt2.rel_name as rel_name2, 
				srt2.rel_dir as rel_dir2
			from survey_relationship_types srt1
				inner join survey_relationship_types srt2 on srt1.rel_group_id=srt2.rel_group_id
				where srt1.rel_dir=1 and srt2.rel_dir=0
			)
			relationship_types order by id;';
		
		$types=$this->db->query($sql)->result_array();
		
		$output=array();
		
		foreach($types as $type)
		{
			$output[$type['id']]=$type['rel_name'].' <---> '.$type['rel_name2'];
		}
		
		return $output;
	}
}