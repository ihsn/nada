<?php
class Repository_model extends CI_Model {
 
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
		$db_fields=array('repositoryid', 'title','url','organization');
		
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
		$this->db->from('repositories');
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
          return $this->db->count_all_results('repositories');
    }
	
	/**
	* Select a single repository item
	*
	**/
	function select_single($id){
		$this->db->where('id', $id); 
		return $this->db->get('repositories')->row_array();
	}
	
	
	/**
	* Select surveys by repositoryid
	*
	**/
	function get_surveys_by_repository($repositoryid){
		$this->db->where('repositoryid', $repositoryid); 
		return $this->db->get('harvester_queue')->result_array();
	}
	

	/**
	*
	* Add/update items in the harvester queue
	*
	* Status checks
	*	- new	- not in the catalog
	*	- changed	- checksum is different
	* 	- deleted	- 
	*/
	function update_queue($repositoryid, $options)
	{
		$allowed_fields=array('retries','repositoryid','survey_url',
						'status','ddi_local_path','changed','title',
						'survey_timestamp', 'retries','country','survey_year',
						'accesspolicy','checksum','surveyid');
		
		$data=array();
		foreach($options as $key=>$value)
		{
			if (in_array($key,$allowed_fields))
			{
				$data[$key]=$value;
			}
		}
		
		$data['repositoryid']=$repositoryid;
		
		//check if exists
		$queued_item=$this->get_queue_item($repositoryid,$options['survey_url']);
		
		//check if survey is already harvested
		$isharvested=$this->survey_exists($repositoryid,$options['surveyid']);
		
		if ($queued_item)
		{			
			$data['status']=$queued_item['status'];
			/*if (!$isharvested)
			{
				$data['status']="new";
			}
			else
			{
				$data['status']="harvested";
			}*/

			$this->db->where('survey_url',$options['survey_url']);
			$this->db->where('repositoryid',$repositoryid);
			$this->db->update('harvester_queue', $data); 
		}
		else
		{
			if (!$isharvested)
			{
				$data['status']="new";
			}
			else
			{
				$data['status']="harvested";
			}
			
			//insert db
			$result=$this->db->insert('harvester_queue', $data); 
		}	
	}

	/**
	*
	* Checks if a survey exists in the queue
	**/
	function check_queue_item_exists($repositoryid,$survey_url)
	{
		$this->db->where('repositoryid', $repositoryid); 
		$this->db->where('survey_url', $survey_url); 
		$row=$this->db->get('harvester_queue')->row_array();
		
		if (count($row)>0)
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	* Return queue item array
	*
	**/
	function get_queue_item($repositoryid,$survey_url)
	{
		$this->db->where('repositoryid', $repositoryid); 
		$this->db->where('survey_url', $survey_url); 
		$row=$this->db->get('harvester_queue')->row_array();
		
		return $row;
	}


	function update_survey_data_access($repositoryid,$surveyid,$data_access_type)
	{
		//get survey id
		$survey_id=$this->get_survey_id($repositoryid,$surveyid);
		
		//get data acess form id
		$this->db->select("formid");
		$this->db->where('model', $data_access_type); 
		$form_row=$this->db->get('forms')->row_array();
		
		if ($form_row)
		{
			//update survey form id
			$data=array('formid'=>$form_row['formid']);
			$this->db->where('id', $survey_id); 
			$this->db->update("surveys",$data);	
			
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	*
	* Update status codes for harvester queue items
	*
	* Status checks
	*	- new	- not in the catalog
	*	- changed	- checksum is different
	* 	- deleted	- 
	*/
	/*function update_queue_stats()
	{
		$queue=$this->db->get('harvester_queue')->result_array();
		
		foreach($queue as $row)
		{
			$exists=$this->survey_exists($row['repositoryid'],$row['surveyid']);
			if (!$exists)
			{
				//not found, mark as new
				$this->update_status_code($id,"NEW");
			}
			else
			{
				$this->update_status_code($id,"HARVESTED");
			}
		}
		
		//delete
		
		
		
		if (count($rows)>0)
		{
			return TRUE;
		}
		return FALSE;
	}
	*/
	
	
	function update_status_code($id,$status)
	{
		$data=array('status'=>$status);
		$this->db->where('id', $id); 
		$this->db->update("harvester_queue",$data);
	}
	
	

	//check if a survey exists in the catalog
	function survey_exists($repositoryid,$surveyid)
	{
		$this->db->where('repositoryid', $repositoryid); 
		$this->db->where('surveyid', $surveyid); 
		$rows=$this->db->get('surveys')->result_array();
		
		if (count($rows)>0)
		{
			return TRUE;
		}
		return FALSE;	
	}
	
	function get_survey_id($repositoryid,$surveyid)
	{
		$this->db->select("id");
		$this->db->where('repositoryid', $repositoryid); 
		$this->db->where('surveyid', $surveyid); 
		$rows=$this->db->get('surveys')->result_array();
		
		if (count($rows)>0)
		{
			return $rows[0]['id'];
		}
		return FALSE;
	}
	
	function get_row($repositoryid,$surveyid)
	{
		$this->db->select("harvester_queue.*,repositories.url as repo_url, repositories.title as repo_title");
		$this->db->join('repositories', 'harvester_queue.repositoryid = repositories.repositoryid','left');
		$this->db->where('harvester_queue.repositoryid', $repositoryid); 
		$this->db->where('harvester_queue.surveyid', $surveyid); 
		$query=$this->db->get('harvester_queue');
		$rows=$query->result_array();

		if (count($rows)>0)
		{
			return $rows[0];
		}
		return FALSE;
	}
	
	/**
	*
	* Return all queued items
	*
	**/
	/*function load_queue()
	{
		//$this->db->where('status!=', "'completed'",FALSE); 
		$query=$this->db->get('harvester_queue');
	//	echo $this->db->last_query();
		return $query->result_array();
	}*/

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

	/**
	*
	* update survey info in queue
	**/
	function update_queued_survey($survey_url,$options)
	{
		$fields=array('retries','repositoryid','survey_url',
						'status','ddi_local_path','changed','title',
						'survey_timestamp', 'retries','country','survey_year');
		
		$data=array();

		foreach($options as $key=>$value)
		{
			if (in_array($key,$fields))
			{
				$data[$key]=$value;
			}
		}
				
		$this->db->where('survey_url',$survey_url);
		//$data=array('status'=>$status, 'retries'=>$retries, 'ddi_local_path'=>$ddi_path);
		
		$query=$this->db->update('harvester_queue',$data);
		//echo $this->db->last_query();
		return $query;
	}




	/**
	* update 
	*
	*	id			int
	* 	options		array
	**/
	function update($id,$options)
	{
		//allowed fields
		$valid_fields=array(
			'repositoryid',
			'title',
			'url',
			'organization',
			'country',
			'status',
			'changed',
			'scan_interval',
			'scan_lastrun',
			'short_text',
			'long_text',
			'thumbnail'
			);

		//add date modified
		$options['changed']=date("U");
					
		//pk field name
		$key_field='id';
		
		$data=array();
		
		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		//update db
		$this->db->where($key_field, $id);
		$result=$this->db->update('repositories', $data); 

		return $result;		
	}



/**
	* add 
	*
	* 	options			array
	**/
	function insert($options)
	{
		//allowed fields
		$valid_fields=array(
			'repositoryid',
			'title',
			'url',
			'organization',
			'country',
			'status',
			'changed'
			);

		//add date modified
		$options['changed']=date("U");
							
		$data=array();
		
		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		//insert record into db
		$result=$this->db->insert('repositories', $data); 
		
		return $result;		
	}


	/**
	* checks if a URL exists
	*
	*/
	function url_exists($url,$id=NULL)
	{
		$this->db->select('id');		
		$this->db->from('repositories');		
		$this->db->where('url',$url);		
		if ($id!=NULL)
		{
//			$this->db->where('id',$id);		
			$this->db->where('id !=', $id);
		}
        $result= $this->db->count_all_results();
		return $result;
	}
	
	/**
	*
	* Delete a repository
	* 	- removes entries from surveys 
	*	- removes entries from harvester_queue
	**/
	function delete($id=NULL)
	{
		if (!is_numeric($id))
		{
			return FALSE;
		}
		
		//get repository info
		$repo=$this->select_single($id);
		
		if (!$repo)
		{
			return FALSE;
		}
		
		//get repositoryid
		$repositoryid=$repo['repositoryid'];
		
		//variables
		$sql[]=sprintf('delete from %s where surveyid_fk in (select id from %s where repositoryid=%s);',
					$this->db->dbprefix('variables'),
					$this->db->dbprefix('surveys'),					
					$this->db->escape($repositoryid) );
		
		//surveys
		$sql[]=sprintf('delete from %s where repositoryid=%s)',
					$this->db->dbprefix('surveys'),
					$repositoryid);
					
		//harvester_queue
		$sql[]=sprintf('delete from %s where repositoryid=%s)',
					$this->db->dbprefix('harvester_queue'),
					$repositoryid);
		
		//repositories
		$sql[]=sprintf('delete from %s where id=%d',$this->db->dbprefix('repositories'),$id);
		
		//execute
		foreach($sql as $s)
		{
			$result=$this->db->query($s);
			
			if (!$result)
			{
				return FALSE;
			}
		}
		
		return TRUE;	
	}
	
	
	/**
	*
	* Returns an array of all repositories
	*	
	* Note: duplicate function see catalog_model.php
	**/
	function get_repositories()
	{
		$this->db->select('*');
		$query=$this->db->get('repositories');

		if (!$query)
		{
			return array();
		}
		
		$result=$query->result_array();
		
		if (!$result)
		{
			return array();
		}

		//create an array, making the repositoryid array key
		$repos=array();
		foreach($result as $row)
		{
			$repos[$row['repositoryid']]=$row;
		}
	
		return $repos;
	}
	
	
	function get_repository_by_repositoryid($repositoryid)
	{
		$this->db->select('*');
		$this->db->where('repositoryid',$repositoryid);
		$query=$this->db->get('repositories');

		if (!$query)
		{
			return FALSE;
		}
		
		return $query->row_array();
	}
	
	/**
	*
	* Assign a user to a repository
	*
	* @roleid	0=delete role
	**/
	function assign_role($repositoryid,$userid,$roleid)
	{
		//delete existing role
		$this->db->where('userid',$userid);
		$this->db->where('repositoryid',$repositoryid);
		$this->db->delete('user_repositories'); 
		
		//0 to remove role
		if ($roleid==0)
		{
			return;
		}
		
		$options=array(
			'userid'=>$userid,
			'repositoryid'=>$repositoryid,
			'roleid'=>$roleid
			);
		
		//add new role
		$this->db->insert('user_repositories', $options); 
	}
	
	/**
	*
	* Returns Catalog admins for a repo
	*
	**/
	function get_repository_admins($repositoryid=NULL)
	{
		if (!is_numeric($repositoryid))
		{
			return FALSE;
		}

		/*
		$sql='select
			  u.id,u.username,u.email,u.active,
			  ur.roleid as user_repo_role_id,ur.repositoryid as repositoryid,
			  ug.name as user_group_name,
			  rug.name as repo_role_name
			  from users u
			left join user_repositories ur on u.id=ur.userid
			left join user_groups ug on u.group_id=ug.id
			left join repo_user_groups rug on rug.id=ur.roleid
			where ug.name not in (\'admin\',\'user\')
			';
		*/
		$sql='select * from user_repositories where repositoryid='.$this->db->escape($repositoryid);
			
		$query=$this->db->query($sql);
		
		if ($query)
		{
			return $query->result_array();
		}
		
		return FALSE;
	}
}
?>