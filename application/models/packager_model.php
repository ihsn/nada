<?php
class Packager_model extends CI_Model {
 
    public function __construct()
    {	
        parent::__construct();		
		//$this->output->enable_profiler(TRUE);
    }

	//returns all surveys
	public function get_surveys($surveyid_arr=NULL)
	{
		$fields='id,repositoryid,surveyid,titl,varcount,ddifilename,dirpath,link_technical, 
				link_study, link_report, link_indicator, ddi_sh, formid, isshared, isdeleted, 
				link_questionnaire, link_da';
		$this->db->select($fields);
		$this->db->where('published',1); //get only published surveys
		
		if (is_array($surveyid_arr))
		{
			$this->db->where_in('surveyid',$surveyid_arr);
		}
		
		return $this->db->get("surveys")->result_array();		
	}

	//returns surveys by tags
	public function get_surveys_by_tags($tags)
	{
		$fields='surveys.id,repositoryid,surveyid,titl,varcount,ddifilename,dirpath,link_technical, 
				link_study, link_report, link_indicator, ddi_sh, formid, isshared, isdeleted, 
				link_questionnaire, link_da';
		$this->db->select($fields);
		$this->db->join('survey_tags', 'surveys.id= survey_tags.sid','inner');		
		$this->db->where('surveys.published',1); //get only published surveys
		$this->db->where_in('survey_tags.tag',(array)$tags); //get only published surveys
		
		$query=$this->db->get("surveys");
		return $query->result_array();	
	}
	

	//returns all surveys by repositoryid
	public function get_surveys_by_repo($repoid)
	{
		$fields='id,repositoryid,surveyid,titl,varcount,ddifilename,dirpath,link_technical, 
				link_study, link_report, link_indicator, ddi_sh, formid, isshared, isdeleted, 
				link_questionnaire, link_da';
		$this->db->select($fields);
		$this->db->where('repositoryid',$repoid);
		return $this->db->get("surveys")->result_array();		
	}
	
	//get external resources by survey id	
	public function get_resources($id)
	{
		$this->db->select("*");
		$this->db->where('survey_id',$id);
		return $this->db->get("resources")->result_array();
	}
	
	
	//check if study exists by ddi id 
	public function study_exists($surveyid)
	{
		$this->db->select("id");
		$this->db->where('surveyid',$surveyid);
		$result=$this->db->get("surveys")->result_array();
		
		if ($result)
		{
			return $result[0]['id'];
		}
		
		return FALSE;
	}
	
	//update study options
	public function update_study_options($survey_obj)
	{
		//get survey id
		$id=$this->study_exists($survey_obj->surveyid);
		
		if(!$id)
		{
			return FALSE;
		}	
		
		//data to be updated
		$data['link_technical']=$survey_obj->link_technical;
		$data['link_study']=$survey_obj->link_study;
		$data['link_report']=$survey_obj->link_report;
		$data['link_indicator']=$survey_obj->link_indicator;
		$data['formid']=$survey_obj->formid;
		$data['link_questionnaire']=$survey_obj->link_questionnaire;
		$data['link_da']=$survey_obj->link_da;
	
		$this->db->where('id', $id);
		return $this->db->update("surveys", $data);	
	}


	//update harvester_queue
	public function update_harvester_queue($survey_obj)
	{
		//get survey id
		$id=$this->study_exists($survey_obj->surveyid);
		
		if(!$id)
		{
			return FALSE;
		}
		
		//get survey repository info
		$repo=$this->get_survey_repository($survey_obj->surveyid);
		
		if(!$repo)
		{
			return FALSE;
		}
		
		//data to be updated
		$data['repositoryid']=$repo["repositoryid"];
		$data['survey_url']=$repo["url"].'index.php/catalog/'.$survey_obj->id;
		$data['status']='harvested';
		$data['title']=$survey_obj->titl;
		$data['accesspolicy']=$survey_obj->formid;
		//$data['country']=$survey_obj->nation;
		$data['surveyid']=$survey_obj->surveyid;
		$data['survey_timestamp']=date("U");
	
		//delete existing
		$this->db->where('surveyid', $survey_obj->surveyid);
		$this->db->delete("harvester_queue");
	
		//insert
		$this->db->where('surveyid', $survey_obj->surveyid);
		return $this->db->insert("harvester_queue", $data);	
	}	
	
	
	//return repository by surveyid
	public function get_survey_repository($surveyid)
	{
		$this->db->select("surveys.repositoryid,url");
		$this->db->join('surveys', 'surveys.repositoryid= repositories.repositoryid','inner');		
		$this->db->where("surveyid",$surveyid);
		return $this->db->get("repositories")->row_array();
	}
	
	
	//import external resources
	public function import_resources($surveyid,$resources_array)
	{
		//check if survey exists
		$id=$this->study_exists($surveyid);
		
		if (!$id)
		{
			return FALSE;
		}
		
		//validate resources
		if (!is_array($resources_array))
		{
			return FALSE;
		}
		
		//import resources
		foreach($resources_array as $resource)
		{
			$resource=(array)$resource;
			//remove columns not needed for insert
			unset($resource['resource_id']);
			
			//set correct surveyid
			$resource['survey_id']=$id;
			
			//insert resource			
			$result=$this->db->insert('resources', $resource); 				
		}
	}

	//delete external resources by surveyid
	public function delete_resources($surveyid)
	{
		//check if survey exists
		$id=$this->study_exists($surveyid);
		
		if (!$id)
		{
			return FALSE;
		}
		
		//delete resources
		$this->db->where('survey_id',$id);
		$this->db->delete('resources'); 				
	}


	//import users and user meta info
	public function import_users($users_arr)
	{		
		//validate resources
		if (!is_array($users_arr) && !is_object($users_arr))
		{
			return FALSE;
		}
		
		//import resources
		foreach($users_arr as $user)
		{
			$user=(array)$user;
			//remove columns not needed for insert
			unset($user['id']);
			
			//reset user group to user
			$user['group_id']=2;
						
			//check if user account already exists
			$user_id=$this->user_exists($user['email']);
			
			if (!$user_id && isset($user['meta']))
			{				
				echo 'adding user '. $user['email'].'<br>';
				
				//store user meta in a local variable for later			
				$user_meta=$user['meta'];
				
				//remove meta from array
				unset($user['meta']);
				
				//insert user
				$result=$this->db->insert('users', $user);
				
				if ($result)
				{
					echo '[success]<br/>';

					//get user id to add user meta info
					$user_id=$this->user_exists($user['email']);
					
					if ($user_id!==FALSE)
					{
						$user_meta=(array)$user_meta;
						unset($user_meta['id']);
						$user_meta['user_id']=$user_id;
						$this->db->insert('meta',$user_meta);
					}
				}
				else
				{
					echo $this->db->last_query();exit;
				}
			}
		}//end-for-each
	}
	
	//check if user exists
	public function user_exists($email=NULL)
	{
		$this->db->select("id");
		$this->db->where('email',$email);
		$query=$this->db->get("users")->row_array();
		
		if ($query && count($query>0))
		{
			return $query['id'];
		}
		
		return FALSE;
	}
	
	
	/*
	*
	* Get survey info
	*
	*/
	public function get_survey($id)
	{
		$this->db->select('id,surveyid,repositoryid,titl,nation,data_coll_start,data_coll_end,ddifilename,dirpath,link_technical,link_study,link_report,link_indicator,formid,changed,created,link_questionnaire,published,link_da');
		$this->db->where('id',$id);
		$query=$this->db->get('surveys');
		
		if ($query)
		{
			return $query->row_array();
		}
		
		return FALSE;
	}
	
	public function set_survey_options($surveyid,$options)
	{
		$this->db->where('surveyid',$surveyid);
		$this->db->update('surveys',$options);
	}
	
	/**
	*
	* return all citation IDs
	**/
	public function get_citations_ID_array()
	{
		$this->db->select('id');
		return $this->db->get('citations')->result_array();
	}
	
	/**
	*
	* Return survey internal id by codebookid/alias
	**/
	function get_survey_uid($survey_id)
	{		
		//from aliases table
		$this->db->select('sid');
		$this->db->where(array('alternate_id' => $survey_id) );
		$query=$this->db->get('survey_aliases')->result_array();

		if ($query)
		{
			return $query[0]['sid'];
		}
		
		//from survey table
		$this->db->select('id');
		$this->db->where(array('surveyid' => $survey_id) );
		$query=$this->db->get('surveys')->row_array();
		
		if ($query)
		{
			return $query['id'];
		}
		
		return FALSE;
	}


	/**
	*
	* Update/Insert citation in the database
	*
	* Note: if citation already exists, update other insert a new one
	**/
	function update_citation($citation,$related_surveys)
	{
		//check if citation already exists				
		$citation_id=$this->citation_exists($citation['ihsn_id']);
		
		//remove elements from citation object that are not needed for update/insert
		unset($citation['related_surveys']);
		unset($citation['id']);

		if ($citation_id)
		{
			//update existing
			$this->Citation_model->update($citation_id,$citation);
		}
		else
		{
			//insert new
			$citation_id=$this->Citation_model->insert($citation);
		}
		
		//remove existing attachments
		//$this->Citation_model->delete_related_survey($citation_id);
		
		//update related surveys		
		$this->Citation_model->attach_related_surveys($citation_id,$related_surveys);		
		
		return $citation_id;
	}
	
	/**
	*
	* Check if citation already exists
	*
	**/
	function citation_exists($ihsn_id)
	{
		$this->db->select('id');
		$this->db->where('ihsn_id',$ihsn_id);
		$query=$this->db->get('citations')->row_array();
		
		if ($query)
		{
			return $query['id'];
		}
		
		return FALSE;
	}
	
	
	/*
	*
	* Get survey aliases
	*/
	function get_survey_alias($sid)
	{
		$this->db->select('alternate_id');
		$this->db->where(array('sid' => sid) );
		$query=$this->db->get('survey_aliases')->result_array();

		if (!$query)
		{
			return FALSE;
		}
		
		$output=array();
		foreach($query as $row)
		{
			$output[]=$row['alternate_id'];
		}
		return $output;
	}
	
	/*
	*
	* Return all survey aliases for all surveys
	*/
	function get_all_survey_aliases()
	{
		$this->db->select('sid,alternate_id');
		$rows=$this->db->get('survey_aliases')->result_array();
		
		$output=array();
		foreach($rows as $row)
		{
			$output[$row['sid']]=$row;
		}
		
		return $output;
	}
}
