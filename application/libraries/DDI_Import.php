<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * DDI-to-DB Import Class
 * 
 * Includes functions to add ddi data to database
 *
 *
 *
 *
 * @package		NADA 3.0
 * @subpackage	Libraries
 * @category	DDI-to-DB Import
 * @author		Mehmood Asghar
 * @link		-
 *
 */
class DDI_Import{    	
	
	//array of study and variables array 	
	var $ddi_array;
	
	//repository identifier for the imported survey
	var $repository_identifier;
		
	var $ci;
	
	var $id;//survey database id
	
	var $errors=array();
	
	var $variables_imported=0; //no. of variables imported
	
    //constructor
	function DDI_Import()
	{
		$this->ci =& get_instance();
		
		//set to local repository by default
		$this->repository_identifier=$this->ci->config->item('repository_identifier');
    }

	/**
	*
	* Import DDI file - study + variable information
	*
	* @return boolean
	*/
	function import($data,$ddi_file_path,$overwrite=FALSE)
	{	
		if ( !is_array($data) )
		{
			$this->errors[]='DDI_Import:: No data was provided for import';
			return false;
		}
		
		$this->ddi_array=$data;
		
		//check if the survey already exists
		if ($overwrite!==TRUE)
		{
			//check if survey already exists
			$survey_exists=$this->survey_exists($surveyid=$this->ddi_array['study']['id'],$repositoryid=$this->repository_identifier);
			
			if($survey_exists!==FALSE)
			{
				$this->errors[]=t('study_already_exists');
				return FALSE;
			}
		}
		
		//import study description
		$result=$this->import_study();
					
		if ($result!==false)
		{	
			//copy survey file to the repository
			$survey_path=$this->_copy_survey_file(
						$ddi_file_path,
						$surveyid=$data['study']['id'],
						$repositoryid=$this->repository_identifier
						);

			if ($survey_path===false)
			{
				//$this->ci->db->trans_rollback();
				return false;
			}
			
			//update survey path in database
			$this->update_survey_pathinfo();

			//import variables		
			$this->import_variables();
	
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	*
	* Import only the Study description part to database
	*
	* return @int	database row id
	*
	**/
	function import_study()
	{		
		$data=$this->ddi_array['study'];
		$data=(object)$data;

		//insert study description
		$row = array(
			'repositoryid'=>$this->repository_identifier,
			'surveyid'=>substr($data->id,0,200), 
			'titl'=>substr($data->titl,0,254), 
			'titlstmt'=>$data->titlstmt,
			'authenty'=>substr($data->authenty,0,254),
			'geogcover'=>substr($data->geogcover,0,254),
			'nation'=>substr($data->nation,0,99),
			'topic'=>serialize($data->topics),
			'scope'=>$data->scope,
			'sername'=>substr($data->sername,0,254),
			'producer'=>substr($data->producer,0,254),
			'refno'=>substr($data->refno,0,254),
			'proddate'=>substr($data->proddate,0,44),
			'sponsor'=>substr($data->sponsor,0,254),
			'data_coll_start'=>(integer)$data->data_coll_start,
			'data_coll_end'=>(integer)$data->data_coll_end,
			'changed'=>date("U"),
			'isdeleted'=>0,
		);
	
		//production date, use the max date
		// todo:fix this is a workaround, catalog search MUST use the data_coll_start or end 
		// dates for sorting and searching instead of using proddate
		if ($row['data_coll_end']>=$row['data_coll_start'])
		{
			$row['proddate']=$row['data_coll_end'];
		}
		else
		{
			$row['proddate']=$row['data_coll_start'];
		}	
	
		//check if survey already exists
		$id=$this->survey_exists($surveyid=$data->id,$repositoryid=$this->repository_identifier);

		//new survey
		if($id===false)
		{
			$row['created']=date("U");
			$insert_result=$this->ci->db->insert('surveys', $row);
			
			if ($insert_result!==FALSE)
			{
				//get the ID for newly added survey
				$id=$this->survey_exists($surveyid=$data->id,$repositoryid=$this->repository_identifier);
			}	
		}
		else //existing survey
		{
			$where=sprintf('id=%d',$id);
			$sql= $this->ci->db->update_string('surveys', $row,$where);			
			$this->ci->db->query($sql);
		}
		
		//check for errors
		if ($this->ci->db->_error_message() )
		{
				$error='Study-import::Database Error: '.$this->ci->db->_error_message();
				$this->errors[]=$error;
				$error.="\r\n".$this->ci->db->last_query();				
						
				//add to error log file	
				log_message('error', $error);				
				return FALSE;			
		}

		//update data collection dates
		$this->update_data_collection_dates($id, $row);
		
		//remove old topic mappings
		$this->ci->db->delete('survey_topics',array('sid' => $id));

		//import topics
		if ($data->topics)
		{			
			foreach($data->topics['topic'] as $topic)
			{
				$pos=strrpos($topic['name'],' ');//position of the last space
				/*if (substr($topic['name'],$pos+1,1)=='[')
				{				
					//remove the toolkit numbers e.g. xxxx xxxx [1.2] will become xxxx xxxxx
					$topic_title=substr($topic['name'],0,strrpos($topic['name'],' '));
				}
				else
				{
					$topic_title=$topic['name'];
				}*/
				$topic_title=trim($topic['name']);
				
				//get topic id
				$topic_id=$this->get_topic_by_title($topic_title);
				
				//add topic to db
				if ($topic_id!==FALSE)
				{				
					$topic_row=array('sid'=>$id, 'tid'=>$topic_id);
					$this->ci->db->insert('survey_topics', $topic_row);
					//var_dump($topic_row);
				}	
			}
		}
		
		$this->id=$id;
		
		//success, return the survey row id
		return $id;
	}
	

	/**
	*
	* Build a range of data collection years range
	* 
	* It uses the start and end as range and add each year as a new row
	* in the database. 
	* 
	* e.g. for range 2005-2010, there will be 6 rows in the survey_rows
	*/
	function update_data_collection_dates($surveyid, $row)
	{
		//remove existing dates if any
		$this->ci->db->delete('survey_years',array('sid' => $surveyid));
		
		$start=(integer)$row['data_coll_start'];
		$end=(integer)$row['data_coll_end'];
		
		if ($start==0)
		{
			$start=$end;
		}
		
		if($end==0)
		{
			$start=$end;
		}
		
		//build an array of years range
		$years=range($start,$end);

		//insert dates into database
		foreach($years as $year)
		{
			$options=array(
						'sid' => $surveyid,
						'data_coll_year' => $year);
			//insert			
			$this->ci->db->insert('survey_years',$options);
		}
	}

	/**
	*
	* Import survey variables to the database
	*
	**/
	function import_variables()
	{
		$survey_id=substr($this->ddi_array['study']['id'],0,200);
		$id=$this->survey_exists($survey_id,$this->repository_identifier);
		
		if($id===false)
		{
			$error='import_variables::failed to find the survey.';
			
			$this->errors[]=$error;			
			log_message('error', $error);
			
			return false;
		}
		
		$data=$this->ddi_array['variables'];
		
		//delete existing variables for the survey
		$this->ci->db->delete('variables', array('surveyid_fk' => $id)); 
		
		$this->variables_imported=0;
		
		foreach($data as $v)
		{
			$row = array(
			   'varid' => $v[0],
			   'name' => $v[1],
			   'labl' => $v[2],
			   'qstn' => trim($v[3]),
			   'catgry' => trim($v[4]),
			   'surveyid_fk' => $id
			);
		
			$this->ci->db->insert('variables', $row);
		
			//check for errors
			if ($this->ci->db->_error_message() )
			{
				$error='import_variables::failed to insert variables: '.$this->ci->db->_error_message();
				$error.="\r\n".$this->ci->db->last_query();
				
				$this->errors[]=$error;
						
				//add to error log file	
				log_message('error', $error);
				
				return false;
			}
			
			$this->variables_imported++;			
		}
		
		//update varcount field
		$row=array('varcount'=>$this->variables_imported);
		$where=sprintf('id=%d',$id);
		$sql= $this->ci->db->update_string('surveys', $row,$where);			
		$this->ci->db->query($sql);
		
		return true;
	}
	
	
	/**
	* Update database with survey folder path
	*
	*/
	function update_survey_pathinfo()
	{		
		$surveyid=$this->ddi_array['study']['id'];
		$id=$this->survey_exists($surveyid,	$this->repository_identifier);
		
		$row = array(
				'ddifilename'=>"$surveyid.xml",
				'dirpath'=>$this->repository_identifier.'/'.md5($this->repository_identifier.':'.$surveyid)
				);
			
		$where=sprintf('id=%d',$id);
		$sql= $this->ci->db->update_string('surveys', $row,$where);
		$this->ci->db->query($sql);
	}
	
	/**
	* copy DDI file to the catalog folder
	*
	*/
	function _copy_survey_file($ddi_source_path,$surveyid,$repositoryid)
	{
		$survey_folder=$this->_get_survey_folder($surveyid,$repositoryid);
		
		if ($survey_folder!==false)
		{
			$survey_file_path=$survey_folder."/$surveyid.xml";
			
			//copy the ddi file 			
			if ( !copy($ddi_source_path,$survey_file_path) ) 
			{
				$this->errors[]= "File was not copied ". $survey_file_path;
				return false;
			}
			else
			{
				return $survey_file_path;
			}
		}
		return false;
	}
	
	
	/**
	* Returns the survey folder path, 
	* if the survey folder does not exists, it will create it.
	*
	* @return	string	path to the survey folder 
	*/	
	function _get_survey_folder($surveyid,$repositoryid)
	{
		$catalog_root=$this->ci->config->item('catalog_root');
		
		//repository folder path
		$repository_folder=$catalog_root."/$repositoryid";
		
		//survey folder path
		$survey_folder=$repository_folder.'/'.md5("$repositoryid:$surveyid");
		
		//check repository folder
		if (!file_exists($repository_folder) )
		{
			if ( !mkdir($repository_folder) ) 
			{//create folder
				$error= "Failed to create new folder for the repository ". " " . $repository_folder;
				$this->errors[]=$error;
				log_message('error', $error);
				return false;
			}
		}
		
		//check survey folder
		if (!file_exists($survey_folder) )
		{
			if ( !mkdir($survey_folder) ) 
			{//create folder
				$error= "Failed to create new folder for the survey ". " " . $survey_folder;
				$this->errors[]=$error;
				log_message('error', $error);
				return false;
			}
		}
		
		return $survey_folder;
	}	
	
	/**
	*
	* check if the survey already exists?
	**/
	function survey_exists($surveyid,$repositoryid)
	{
		$this->ci->db->select('id');
		$this->ci->db->from('surveys');
		$this->ci->db->where(array('surveyid' => $surveyid,'repositoryid' => $repositoryid) );
		$query=$this->ci->db->get();

		if ($query->num_rows() > 0)
		{
		   foreach ($query->result() as $row)
		   {
				return $row->id;
		   }
		}
		return false;		
	}
	
	
	
	/**
	* Returns Topic ID by topic name
	*
	*/
	function get_topic_by_title($topic_name)
	{
		$this->ci->db->select('tid');
		$this->ci->db->from('terms');
		$this->ci->db->where('title',$topic_name);
		$query=$this->ci->db->get();
		//print $this->ci->db->last_query();
		if ($query->num_rows() > 0)
		{
		   foreach ($query->result() as $row)
		   {
				return $row->tid;
		   }
		}
		return FALSE;
	}
}// END DDI Import Class

/* End of file DDI_Import.php */
/* Location: ./application/libraries/DDI_Import.php */