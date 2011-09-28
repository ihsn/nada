<?php
class Harvester_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	//search
    function search($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL)
    {
		$this->db->start_cache();

		//select columns for output
		$this->db->select('*');
		
		//allowed_fields
		$db_fields=array('repositoryid', 'title','survey_url','status','ddi_local_path','retries');
		
		//set where
		if ($filter)
		{			
			foreach($filter as $f)
			{
				//search only in the allowed fields
				if (in_array($f['field'],$db_fields))
				{
					$this->db->like($f['field'], $f['keywords']); 
				}
				else if ($f['field']=='all')
				{
					foreach($db_fields as $field)
					{
						$this->db->or_like($field, $f['keywords']); 
					}
				}
			}
		}

		//set order by
		if ($sort_by!='' && $sort_order!='')
		{
			$this->db->order_by($sort_by, $sort_order); 
		}
		
		//set Limit clause
	  	$this->db->limit($limit, $offset);
		$this->db->from('harvester_queue');
		$this->db->stop_cache();

        $result= $this->db->get();
		if ($result)
		{			
			$result=$result->result_array();
			return $result;
		}
		else
		{
			echo $this->db->last_query();
			return FALSE;
		}	
    }
  	
    function search_count()
    {
          return $this->db->count_all_results('harvester_queue');
    }
	
	
	/**
	* Select a single item
	*
	**/
	function select_single($id){
		$this->db->where('id', $id); 
		return $this->db->get('harvester_queue')->row_array();
	}
	

	/**
	*
	* Add/update items in the harvester queue
	*
	*/
	function update_queue($repositoryid, $options)
	{
		$allowed_fields=array('repositoryid','title','survey_url', 'status', 'changed', 'survey_timestamp');
		
		$data=array();
		foreach($options as $key=>$value)
		{
			if (in_array($key,$allowed_fields))
			{
				$data[$key]=$value;
			}
		}
		
		$data['repositoryid']=$repositoryid;
		
		//insert db
		$result=$this->db->insert('harvester_queue', $data); 
	}

	/**
	*
	* Return all queued items
	*
	**/
	function load_queue()
	{
		//$this->db->where('status!=', "'completed'",FALSE); 
		$query=$this->db->get('harvester_queue');
	//	echo $this->db->last_query();
		return $query->result_array();
	}


	//get a single item from the queue for processing
	function queue_pop($id=NULL)
	{
		//$this->db->where('status!=', "'completed'",FALSE);
		$this->db->where('retries<', 3,FALSE); 
		
		if($id!=NULL)
		{
			$this->db->where('id', (int)$id); 
		}
		
		$query=$this->db->get('harvester_queue',1,0);
		//echo $this->db->last_query();
		return $query->row_array();
	}




	function update_queued_survey($survey_url,$status,$retries=3,$ddi_path='')
	{
		$this->db->where('survey_url',$survey_url);
		$data=array('status'=>$status, 'retries'=>$retries, 'ddi_local_path'=>$ddi_path);
		
		$query=$this->db->update('harvester_queue',$data);
		//echo $this->db->last_query();
		return $query;
	}


	function delete($surveyid)
	{
		$this->db->where('id',$surveyid);
		return $this->db->delete('harvester_queue');		
	}

	function update_status($surveyid,$status)
	{
		$this->db->where('id',$surveyid);
		$data=array('status'=>$status);
		$query=$this->db->update('harvester_queue',$data);
		return $query;
	}
}
?>