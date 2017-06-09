<?php
class Topic_search_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
  	
	//return single form row
	function get_single($formid){
		$this->db->where('formid', $formid); 
		return $this->db->get('forms')->row_array();
	}
	
	//return all forms
	function get_all(){
		return $this->db->get('forms')->result_array();
	}
	
	
	function get_topic_groups()
	{
		
		$sql='select *,count(*) as surveys_found from topics t
  					inner join survey_topics st on st.tid=t.tid
					group by st.tid
				';

		return $this->db->query($sql)->result_array();		
	}
	
	
	//topic_id=array
	function get_surveys_by_topic($topic_id)
	{
		if (is_array($topic_id))
		{
			$id_list=implode(',',$topic_id);
		}	
		$sql='select surveys.*,forms.model as form_model from surveys 
				left join forms on surveys.formid=forms.formid';		
		
		if (is_array($topic_id))
		{
			$sql.=' 
				left join survey_topics on surveys.id=survey_topics.sid
				where tid in ('.$id_list.') group by surveys.id';
		}
		else
		{
			$sql.=' limit 0,20';
		}
						
		$surveys=$this->db->query($sql)->result_array();
		
		
		//sadaf	0342 528 1433
		
		if (is_array($topic_id))
		{
			//get topics by topic id
			$topic_sql='select topics.*,survey_topics.sid from topics 
							inner join survey_topics on topics.tid=survey_topics.tid
							where topics.tid in ('.$id_list.')';
			
			//get topic rows
			$topics_list=$this->db->query($topic_sql)->result_array();
			
			//link topics to the surveys
			foreach($surveys as $key=>$row)
			{
				//find topics and attach
				foreach($topics_list as $topic)
				{
					if ($row['id']==$topic['sid'])
					{
						$surveys[$key]['topics'][]=$topic['title'];
					}
				}
			}
		}	
		
		return $surveys;
		
	}

	function get_all_topics()
	{
		$sql='select t.*,count(st.sid) as surveys_found from topics t
			  left join survey_topics st on t.tid=st.tid
			  group by t.tid
			  order by pid';		
		
		return $this->db->query($sql)->result_array();
	}
}
?>