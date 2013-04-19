<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * NADA Catalog Admin Library
 * 
 *
 *
 *
 * @package		NADA 4.0
 * @subpackage	Libraries
  * @author		Mehmood Asghar
 * @link		-
 *
 */ 
class Catalog_Admin
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		log_message('debug', "Catalog Admin Class Initialized.");
		$this->ci =& get_instance();		
	}

	
	/*
	* Show all files by surveyid
	*
	*/
	function managefiles($surveyid=NULL)
	{
		if (!is_numeric($surveyid))
		{
			return FALSE;
		}
		
		$this->ci->load->helper('file');		
		$this->ci->load->model("managefiles_model");
		$this->ci->load->model("resource_model");
		$this->ci->load->model("form_model");
		
		$this->ci->lang->load("resource_manager");
		
		
		//get survey folder path
		$folderpath=$this->ci->managefiles_model->get_survey_path($surveyid);
		
		$survey_folder=$folderpath;
		
		//name of the file to lock for deletion
		$ddi_file_name=basename($this->ci->Catalog_model->get_survey_ddi_path($surveyid));
		
		//process file upload if any
		//$this->_process_uploads($folderpath);
		
		//process folder create (if any)
		//$this->_create_folder($folderpath);
		
		//process delete files
		//$this->_delete_files($folderpath);
		
		//get all survey files
		$data=$this->ci->managefiles_model->get_files_recursive($folderpath,$folderpath);
		
		//array of folders and subfolders	
		$folders=$data['dirs'];
		
		//get array of resources to check which files are linked
		$resources=$this->ci->managefiles_model->get_resource_paths_array($surveyid);

		//match resources with file path
		foreach($data["files"] as $key=>$file)
		{
			$data["files"][$key]["resource"]=$this->match_resource_paths($resources,$file);
		}

		$data['survey_folder']=$survey_folder;
		$data['ddi_file_name']=$ddi_file_name;
		
		//list of files
		return $this->ci->load->view("catalog/study_files",$data,TRUE);
	}

	//todo: describe function
	function match_resource_paths($resources_array,&$file)
	{
		if (is_array($resources_array))
		{
			foreach($resources_array as $resource)
			{
				if ($resource['filename']==unix_path($file['path'].'/'.$file['name']) )
				{
					return $resource;
				}
			}
		}
		return FALSE;		
	}


	/**
	* returns survey related external resources
	* 
	*/
	function get_formatted_resources($sid)
	{		
		//get all resoruces attached to a survey
		$resources=$this->ci->resource_model->get_survey_resources($sid);
		
		//total resources
		$output['total'] = count($resources);

		//formatted resources list
		$output['formatted']=$this->ci->load->view('catalog/study_resources', array('rows'=>$resources),TRUE);
		
		return $output;
	}


	/**
	*
	* Return a formatted list of collections attached to a study
	*
	**/
	function get_formatted_collections($sid=NULL)
	{
		$this->ci->load->model('repository_model');
		
		//get a list of all survey collections
		$data['collections']=$this->ci->repository_model->get_repositories();
		$data['selected']=array();
		
		if (is_numeric($sid))
		{
			//get collections attached to a study
			$data['selected']=$this->ci->repository_model->get_repo_list_by_survey($sid);
		}	

		return $this->ci->load->view("catalog/study_collections",$data,TRUE);
	}
	
	
}//end class

