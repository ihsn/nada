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
	* supports: sorting, searching, pagination
	*/
	function resources($id)
	{		
		//set parent survey
		$this->ci->resource_model->surveyid=$id;
				
		//records to show per page
		$per_page = 1000;
		
		//current page
		$curr_page=$this->ci->input->get('per_page');//$this->uri->segment(4);

		//records
		$rows=$this->ci->resource_model->search($per_page, $curr_page);

		//total records in the db
		$total = $this->ci->resource_model->search_count;

		if ($curr_page>$total)
		{
			$curr_page=$total-$per_page;
			
			//search again
			$rows=$this->ci->resource_model->search($per_page, $curr_page);
		}
		
		//set pagination options
		$base_url = site_url("admin/catalog/{$id}/resources");
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field','ps'));//pass any additional querystrings
		$config['next_link'] = t('page_next');
		$config['num_links'] = 5;
		$config['prev_link'] = t('page_prev');
		$config['first_link'] = t('page_first');
		$config['last_link'] = t('page_last');
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->ci->pagination->initialize($config); 
		
		return $this->ci->load->view('catalog/study_resources', array('rows'=>$rows),TRUE);
	}


	/**
	*
	* Return a formatted list of terms attached to a study
	*
	**/
	function term_list($vid,$sid=NULL)
	{
		$this->ci->load->model('term_model');
		
		//get a list of all survey collections
		$data['terms']=$this->ci->term_model->get_terms_by_vocabulary($vid);

		$data['selected']=array();
		
		if (is_numeric($sid))
		{
			//get collections attached to a study
			$data['selected']=$this->ci->term_model->get_survey_collections($sid);
		}	

		return $this->ci->load->view("catalog/studycollections",$data,TRUE);
	}
	
	
}//end class

