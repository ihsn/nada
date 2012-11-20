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
		
		//check if survey already exists
		$id=$this->survey_exists($surveyid=$this->ddi_array['study']['id'],$repositoryid=$this->repository_identifier);

		//check if the survey already exists
		if ($overwrite!==TRUE)
		{			
			if($id!==FALSE)
			{
				$this->errors[]=t('study_already_exists');
				return FALSE;
			}
		}
		
		//import study description
		$result=$this->import_study();
					
		if (!$result)
		{
			$this->errors[]=t('database_error_');
			return FALSE;
		}
		else
		{	
			//copy survey file to the repository
			$survey_ddi_path=$this->_copy_survey_file(
									$ddi_file_path,
									$surveyid=$data['study']['id'],
									$repositoryid=$this->repository_identifier);

			if ($survey_ddi_path===false)
			{
				//$this->ci->db->trans_rollback();
				return false;
			}
			
			//get survey folder path
			$survey_folder=$this->_get_survey_folder($this->ddi_array['study']['id'],$this->repository_identifier);
			
			log_message("error", "survye-folder-path::::".$survey_folder);
			
			//update survey path in database
			$this->update_survey_pathinfo($survey_folder);

			//import variables		
			if (!$this->import_variables())
			{
				return FALSE;
			}
			return TRUE;
		}
		
		return FALSE;
	}


	/**
	*
	* replace DDI file - study + variable information
	*
	* @data
	* @ddi_file_path	newly uploaded ddi file path
	* @target_survey_id	ID of the DDI that will be replaced with the new ddi
	*
	* @return boolean
	*/
	function replace($data,$ddi_file_path,$target_survey_id)
	{	
		if ( !is_array($data) )
		{
			$this->errors[]='DDI_Import:: No data was provided for replace';
			log_message('ERROR', 'DDI_Import:: No data was provided for replace');
			return false;
		}
		
		$this->ddi_array=$data;
		
		//check if the survey already exists
		$id=$this->survey_exists($surveyid=$this->ddi_array['study']['id']);
		
		// check if survey already exists in the catalog and ID is not same as the target
		// replace will not replace if duplicate survey exists	
		if ($id!==FALSE && $id!==$target_survey_id)
		{
			$this->errors[]=t('study_already_exists')."{$this->ci->db->last_query()} ddi: {$this->ddi_array['study']['id']} source{$id} target {$target_survey_id} ";
			$this->ci->db_logger->write_log('ddi-replace',"survey exists - ".$id,'catalog');			
			log_message('ERROR', 'ddi replace aborted: study already exists '.$id);
			return FALSE;
		}
				
		//change old study info with new info
		$options=array(
			'surveyid'=>$this->ddi_array['study']['id']
		);						
		$this->ci->db->where("id",$target_survey_id);
		$this->ci->db->update("surveys",$options);
		
		//$this->ci->db_logger->write_log('ddi-replace',"survey exists - ".$id,'catalog');
		log_message('INFO', 'DDI_Import: updated study file to new file');
		
		//import will replace the DDI with new metadata
		return $this->import($data,$ddi_file_path,$overwrite=TRUE);
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
		
		//clean up authenty
		$authenty_arr=explode("{BR}",substr($data->authenty,0,200));
				
		foreach($authenty_arr as $key=>$pi)
		{
			if	($pi=='')
			{
				unset($authenty_arr[$key]);
			}
		}
		
		//countries array
		$nation_arr=explode("{BR}",$data->nation);
		
		foreach($nation_arr as $key=>$value)
		{
			if	(trim($value)=='')
			{
				unset($nation_arr[$key]);
			}
			else
			{
				$nation_arr[$key]=trim($value);
			}
		}

		//insert study description
		$row = array(
			'repositoryid'=>$this->repository_identifier,
			'surveyid'=>substr($data->id,0,200), 
			'titl'=>substr($data->titl,0,254),
			'abbreviation'=>substr(trim($data->abbreviation),0,45),
			'kindofdata'=>substr(trim($data->kindofdata),0,254),
			'titlstmt'=>$data->titlstmt,
			'authenty'=>json_encode($authenty_arr),//substr($data->authenty,0,254),
			'geogcover'=>substr($data->geogcover,0,254),
			'nation'=>substr(implode(", ",$nation_arr),0,255),
			'topic'=>serialize($data->topics),
			'scope'=>$data->scope,
			'keywords'=>trim($data->keywords),
			'sername'=>substr(trim($data->sername),0,254),
			'producer'=>substr($data->producer,0,254),
			'refno'=>substr($data->refno,0,254),
			'proddate'=>substr($data->proddate,0,44),
			'sponsor'=>substr($data->sponsor,0,254),
			//'collections'=>$data->collections,
			'data_coll_start'=>(integer)$data->data_coll_start,
			'data_coll_end'=>(integer)$data->data_coll_end,
			'changed'=>date("U"),
			'isdeleted'=>0,
			'ie_program'=>substr(trim($data->program),0,254),
			'ie_project_id'=>substr(trim($data->ie_project_id),0,254),
			'ie_project_name'=>substr(trim($data->ie_project_name),0,254),
			'ie_project_uri'=>substr(trim($data->ie_project_uri),0,254),
			'ie_team_leaders'=>serialize($data->ie_team_leaders),
			'project_id'=>substr(trim($data->project_id),0,254),
			'project_name'=>substr(trim($data->project_name),0,254),
			'project_uri'=>substr(trim($data->project_uri),0,254),
			'published'=>0,
			'formid'=>6
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
		$id=$this->survey_exists($surveyid=$data->id);

		//new survey
		if(!$id)
		{
			$row['created']=date("U");
			$insert_result=$this->ci->db->insert('surveys', $row);

			if ($insert_result)
			{
				//get the ID for newly added survey
				$id=$this->survey_exists($surveyid=$data->id,$repositoryid=$this->repository_identifier);
			}	
			else
			{
				$this->errors[]='DB_ERROR: '.$this->ci->db->_error_message().'<BR />'.$this->ci->db->last_query();
				log_message('ERROR', 'DDI_Import-DB_ERROR:: '.$this->ci->db->last_query());
				return FALSE;
			}
		}
		else //existing survey
		{
			$where=sprintf('id=%d',$id);
			
			//unset fields
			unset($row['published']);
			unset($row['formid']);
			
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

		//update survey repository info
		$this->update_survey_repos($id,$this->repository_identifier);

		//update data collection dates
		$this->update_data_collection_dates($id, $row);
		
		//update survey countries
		$this->update_survey_countries($id,$nation_arr);
		
		//update survey centers
		if (isset($data->center) && trim($data->center)!="")
		{
			$this->update_orig_arch($id,$data->center);
		}
		
		//remove old topic mappings
		$this->ci->db->delete('survey_topics',array('sid' => $id));

		//import topics
		if ($data->topics)
		{	
			//topics array structure is different for single and multiple topics
			if (isset($data->topics['topic']) && isset($data->topics['topic']['name']))
			{
						$topic_title=trim($data->topics['topic']['name']);
						
						//get topic id
						$topic_id=$this->get_topic_by_title($topic_title);
						
						//add topic to db
						if ($topic_id!==FALSE)
						{				
							$topic_row=array('sid'=>$id, 'tid'=>$topic_id);
							$this->ci->db->insert('survey_topics', $topic_row);
						}	
			}
			else //multiple topics
			{
					foreach($data->topics['topic'] as $topic)
					{
						$topic_title=trim($topic['name']);
						
						//get topic id
						$topic_id=$this->get_topic_by_title($topic_title);
						
						//add topic to db
						if ($topic_id!==FALSE)
						{				
							$topic_row=array('sid'=>$id, 'tid'=>$topic_id);
							$this->ci->db->insert('survey_topics', $topic_row);
						}	
						
					}//end-foreach
				}//end-else			
		}
		
		$this->id=$id;
		
		//success, return the survey row id
		return $id;
	}
	

	/**
	*
	* Add/edit survey countries
	**/
	function update_survey_countries($surveyid, $countries)
	{
		//remove existing survey countries
		$this->ci->db->where('sid',$surveyid);
		$this->ci->db->delete('survey_countries');
		
		$data=array();
		foreach ($countries as $country)
		{
			//get country ISO code
			$iso=$this->get_country_iso($country);
			
			//add to survey_countries
			$this->add_survey_country($surveyid, $country, $iso);
		}		
	}
	
	/**
	*
	* Add a single country to survey
	**/
	function add_survey_country($surveyid,$country_name,$iso)
	{
		$options=array(
					'sid'			=>$surveyid,
					'country_name'	=>$country_name,
					'iso'			=>$iso
				);
		$this->ci->db->insert('survey_countries',$options);
	}
	
	
	/**
	*
	* Return country ISO code by country name
	**/
	function get_country_iso($country_name)
	{
		$this->ci->db->select('iso');
		$this->ci->db->where('name',trim($country_name));
		$query=$this->ci->db->get('countries')->row_array();
		
		if (!$query)
		{
			return FALSE;
		}
		
		return $query['iso'];
	}
		
	
	/**
	*
	* Update/add survey_repos info
	*/
	function update_survey_repos($surveyid,$repositoryid)
	{
		$data=array(
				'sid'=>$surveyid,
				'repositoryid'=>$repositoryid,
				'isadmin'=>1 //give admin rights to the repo that uploaded the survey
			);
		
		//delete any existing entry for the study
		$this->ci->db->where('sid',$surveyid);
		$this->ci->db->where('repositoryid',$repositoryid);		
		$this->ci->db->delete('survey_repos');
		
		//add new info
		$this->ci->db->insert('survey_repos',$data);
		return TRUE;
	}
		
		
		
	function update_collections($surveyid,$coll_str)
	{
		$this->ci->load->model('vocabulary_model','vocabularies');
		$this->ci->load->model('term_model','terms');
		
		//collection array
		$coll_arr=explode("{BR}",$coll_str);
		
		//get COLLECTIONS vocabulary
		$vocab=$this->ci->vocabularies->get_vocabulary_by_title('DDI Collection');
		
		$vocabid=FALSE;		
		if (!$vocab)
		{
			//create vocabulary
			$vocabid=$this->ci->vocabularies->insert('DDI Collection');
			
			if (!$vocabid)
			{
				$this->errors[]='Failed to create vocabulary for Collections';
				return FALSE;
			}
		}
		else
		{
			$vocabid=$vocab['vid'];
		}
		
		//delete existing collections from survey
		$this->ci->db->query('delete from survey_collections where sid='.(int)$surveyid);
				
		//add collections to survey
		foreach($coll_arr as $collection)
		{
			$collection=trim($collection);
			if ($collection=='')
			{
				continue;
			}
			//get collection id
			$term_arr=$this->ci->terms->find_term($collection,$vocabid);

			if (!$term_arr)
			{
				//create new term
				$data=array(
					'vid'=>$vocabid,
					'title'=>$collection
				);
				$collection_id=$this->ci->terms->insert($data);
			}
			else
			{
				$collection_id=$term_arr[0]['tid'];
			}
			
			//link to survey
			$this->update_single_collection($surveyid,$collection_id);
//			echo $this->ci->db->last_query();exit;
		}
		
		return TRUE;
	
	}

	/**
	*
	* Update/Create collection for a survey
	*/
	function update_single_collection($surveyid,$collection_id)
	{
		$data=array(
				'sid'=>$surveyid,
				'tid'=>$collection_id
			);
		$this->ci->db->insert('survey_collections',$data);
		return TRUE;
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
			$result=$this->ci->db->insert('survey_years',$options);
			
			if(!$result)
			{
				$this->errors[]=$this->ci->db->_error_message().'<BR />'.$this->ci->db->last_query();
				return FALSE;
			}
		}
	}
	
	
	/**
	*
	* add/update center info using the element (origArch)
	*
	* Note: Expects element to be a comma separated list 
	*/
	function update_orig_arch($surveyid, $el)
	{
		//remove existing dates if any
		$this->ci->db->delete('survey_centers',array('sid' => $surveyid));
		
		//split centers
		$centers=explode(",",$el);

		//insert dates into database
		foreach($centers as $center)
		{
			$options=array(
						'sid' => $surveyid,
						'center_name' => trim($center));
			//insert			
			$result=$this->ci->db->insert('survey_centers',$options);
			
			if(!$result)
			{
				$this->errors[]=$this->ci->db->_error_message().'<BR />'.$this->ci->db->last_query();
				return FALSE;
			}
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
		$this->ci->db->delete('variables', array('surveyid_FK' => $id)); 
		
		$this->variables_imported=0;
		
		foreach($data as $v)
		{
			$row = array(
			   'varID' => $v[0],
			   'name' => $v[1],
			   'labl' => $v[2],
			   'qstn' => trim($v[3]),
			   'catgry' => trim($v[4]),
			   'surveyid_FK' => $id
			);
		
			$result=$this->ci->db->insert('variables', $row);
			
			//insert failed
			if (!$result)
			{
				$error='import_variables: '.$this->ci->db->_error_message();
				$error.="\r\n".$this->ci->db->last_query();				
				$this->errors[]=$error;
				log_message('error', $error);
				return FALSE;
			}
			
			$this->variables_imported++;			
		}
		
		//update varcount field
		$row=array('varcount'=>$this->variables_imported);
		$where=sprintf('id=%d',$id);
		$sql= $this->ci->db->update_string('surveys', $row,$where);			
		$this->ci->db->query($sql);
		
		return TRUE;
	}
	
	
	/**
	* Update database with survey folder path
	*
	*/
	function update_survey_pathinfo($survey_path=NULL)
	{		
		$surveyid=$this->ddi_array['study']['id'];
		$id=$this->survey_exists($surveyid,	$this->repository_identifier);
		
		$row = array(
				'ddifilename'=>"$surveyid.xml",
				'dirpath'=>$this->repository_identifier.'/'.md5($this->repository_identifier.':'.$surveyid)
				);
		
		if ($id)
		{
			unset($row['dirpath']);//don't update path if survey exists in db
		}
		
		if ($survey_path!==NULL && trim($survey_path)!=='')
		{
			$row['dirpath']=$survey_path;
		}
		
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
		$catalog_root=$this->ci->config->item('catalog_root');

		//check if folder exists
		if (trim($catalog_root)=='' || !file_exists($catalog_root))
		{
				$error= t("error_catalog_root_not_set"). " " . $catalog_root;
				$this->errors[]=$error;
				log_message('error', $error);
				return FALSE;
		}
		
		$survey_folder=$catalog_root.'/'.$survey_folder;
		
		if ($survey_folder!==false)
		{
			$survey_file_path=unix_path($survey_folder."/$surveyid.xml");

			log_message('error', "survey_folder:".$survey_folder);
			log_message('error', "ddi_source_path:".$ddi_source_path);
			log_message('error', "survey_file_path:".$survey_file_path);
						
			//if source and target are same, don't copy
			if (unix_path($ddi_source_path)==($survey_file_path))
			{
				return $survey_file_path;
			}
			
			//copy the ddi file 			
			if (!@copy($ddi_source_path,$survey_file_path) ) 
			{
				$this->errors[]= "File was not copied source:$ddi_source_path, target:$survey_file_path";
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
		//datafiles foldee
		$catalog_root=$this->ci->config->item('catalog_root');

		//check if folder exists
		if (trim($catalog_root)=='' || !file_exists($catalog_root))
		{
				$error= t("error_catalog_root_not_set"). " " . $catalog_root;
				$this->errors[]=$error;
				log_message('error', $error);
				return FALSE;
		}
		
		//check if survey folder is already set in db
		$survey_row=$this->get_survey_by_surveyid($surveyid);
		
		if (count($survey_row)>0)
		{
			if ($survey_row["dirpath"]!==NULL && $survey_row["dirpath"]!="")
			{
				//$path=$catalog_root.'/'.$survey_row['dirpath'];
				return $survey_row['dirpath'];
				//return $path;
			}	
		}
		
		//repository folder path
		$repository_folder=$catalog_root."/$repositoryid";
				
		//survey folder path
		$survey_folder=$repository_folder.'/'.md5("$repositoryid:$surveyid");
		
		//check repository folder
		if (!file_exists($repository_folder) )
		{
			if ( !@mkdir($repository_folder) ) 
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
		log_message('error', "path-normal:".$survey_folder);
		log_message("error", "survey-relative-path:::".$repositoryid.'/'.md5("$repositoryid:$surveyid"));
		
		//return relative survey path
		return $repositoryid.'/'.md5("$repositoryid:$surveyid");
	}	
	
	/**
	*
	* check if the survey already exists?
	*
	* note: repositoryid param is no longer used
	*
	* TODO// remove second param
	**/
	function survey_exists($surveyid,$repositoryid=NULL)
	{
		$this->ci->db->select('id');
		$this->ci->db->from('surveys');
		
		//$this->ci->db->where(array('surveyid' => $surveyid,'repositoryid' => $repositoryid) );		
		
		//check surveyid in all repositories
		$this->ci->db->where(array('surveyid' => $surveyid) );
		
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
	*
	* Return survey info by survey id
	**/	
	function get_survey($id)
	{
		$this->ci->db->select('id,surveyid');
		$this->ci->db->where("id",$id);
		return $this->ci->db->get('surveys')->row_array();		
	}
	
	/**
	*
	* Return survey info by surveyid
	**/	
	function get_survey_by_surveyid($surveyid)
	{
		$this->ci->db->select('id,surveyid,dirpath');
		$this->ci->db->where("surveyid",$surveyid);
		return $this->ci->db->get('surveys')->row_array();		
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