<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * NADA Catalog Admin Library
 * 
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
		$this->ci->load->model("Survey_resource_model");
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


	/*
	* get survey files
	*
	*/
	function get_files_array($surveyid=NULL)
	{
		if (!is_numeric($surveyid))
		{
			return FALSE;
		}

		$this->ci->load->helper('file');
		$this->ci->load->model("managefiles_model");
		$this->ci->load->model("Survey_resource_model");
		$this->ci->load->model("form_model");

		//get survey folder path
		$folderpath=$this->ci->managefiles_model->get_survey_path($surveyid);

		//get all survey files
		$data=$this->ci->managefiles_model->get_files_recursive($folderpath,$folderpath);
		return $data['files'];

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
		$resources=$this->ci->Survey_resource_model->get_survey_resources($sid);

		//total resources
		$output['total'] = count($resources);

		//formatted resources list
		$output['formatted']=$this->ci->load->view('catalog/study_resources', array('rows'=>$resources),TRUE);

		return $output;
	}

	
	/**
	* returns survey data files
	*
	*/
	function get_formatted_data_files($sid)
	{
		$this->ci->load->model("Data_file_resources_model");

		//get all data files for a survey
		$data_files=$this->ci->Data_file_resources_model->get_all_files_resources($sid);

		//total resources
		$output['total'] = count($data_files);

		//formatted resources list
		$output['formatted']=$this->ci->load->view('catalog/data_files', array('rows'=>$data_files),TRUE);

		return $output;
	}


	/**
	*
	* Return a formatted list of collections attached to a study
	*
	**/
	function get_formatted_collections($sid=NULL,$owner_repo=NULL)
	{
		$this->ci->load->model('repository_model');

		//get a list of all survey collections
		$data['collections']=$this->ci->repository_model->get_repositories();

		if(isset($owner_repo[0]['repositoryid'])){
			foreach($data['collections'] as $key=>$collection){
				if($collection['repositoryid']==$owner_repo[0]['repositoryid']){
					unset($data['collections'][$key]);
					break;
				}
			}
		}

		$data['selected']=array();

		if (is_numeric($sid)){
			//get collections attached to a study
			$data['selected']=$this->ci->repository_model->get_repo_list_by_survey($sid,$exclude_owner=1);
		}

		return $this->ci->load->view("catalog/study_collections",$data,TRUE);
	}


	/**
	*
	* Returns PDF document or false
	**/
	function get_study_pdf($id)
	{
		//get survey folder path
		$survey_folder=$this->ci->Catalog_model->get_survey_path_full($id);

		//get ddi file path
		$ddi_file=$this->ci->Catalog_model->get_survey_ddi_path($id);

		//pdf file path
		$pdf_file=unix_path($survey_folder.'/ddi-documentation-'.$this->ci->config->item("language").'-'.$id.'.pdf');

		$output=array();

		if(file_exists($pdf_file))
		{
			$output['path']=$pdf_file;

			//check if PDF is up-to-date
			if(filemtime($pdf_file) > filemtime($ddi_file) )
			{
				$output['status']='uptodate';
			}
			else
			{
				$output['status']='outdated';
			}

			return $output;
		}

		$output['status']='na';
		return $output;
	}


	function delete_study_pdf($id)
	{
		//get survey folder path
		$survey_folder=$this->ci->Catalog_model->get_survey_path_full($id);

		//pdf file path
		$pdf_file=unix_path($survey_folder.'/ddi-documentation-'.$this->ci->config->item("language").'-'.$id.'.pdf');

		return @unlink($pdf_file);
	}


	/**
	* import a ddi file
	*
	*/
	public function import_ddi($ddi_file,$overwrite=FALSE,$repositoryid, $delete_after_import=FALSE)
	{
		//load DDI Parser Library
		$this->ci->load->library('DDI_Parser');
		$this->ci->load->library('DDI_Import','','DDI_Import');

		//check file exists
		if (!file_exists($ddi_file))
		{
			return array(
				'status'=>'error',
				'message'=>'file_not_found'
			);
		}

		//set the repository where the ddi will be uploaded to
		$this->ci->DDI_Import->repository_identifier=$repositoryid;

		//set file for parsing
		$this->ci->ddi_parser->ddi_file=$ddi_file;

		//only available for xml_reader
		$this->ci->ddi_parser->use_xml_reader=TRUE;

		//validate DDI file
		if ($this->ci->ddi_parser->validate()===false)
		{
			return array(
				'status'=>'error',
				'message'=>'invalid_ddi_file'
			);
		}

		//parse ddi to array
		$data=$this->ci->ddi_parser->parse();

		//import to db
		$result=$this->ci->DDI_Import->import($data,$ddi_file,$overwrite);

		$output=NULL;

		if ($result===TRUE)
		{
			//display import success
			//$this->load->view('catalog/ddi_import_success', array('info'=>$data['study']));
			$msg='<strong>'. $data['study']['titl']. '</strong> - <em>'.$this->ci->DDI_Import->variables_imported.' '.t('variables').'</em>';
			//log_message('info', $msg);

			$output= array(
				'status'	=>'success',
				'message'	=>$msg,
				'sid'		=>$this->ci->DDI_Import->id
			);
		}
		else
		{
			$error=$this->ci->load->view('catalog/ddi_import_fail', array('errors'=>$this->ci->DDI_Import->errors),true);
			$output= array(
				'status'=>'error',
				'message'=>$error
			);
		}

		if($delete_after_import===TRUE)
		{
			@unlink($ddi_file);
		}

		return $output;
	}

	/**
	*
	* Import RDF file
	* TODO:// moved to resource_model
	**/
	public function import_rdf($surveyid,$filepath)
	{
		//check file exists
		if (!file_exists($filepath))
		{
			return FALSE;
		}

		//read rdf file contents
		$rdf_contents=file_get_contents($filepath);

		//load RDF parser class
		$this->ci->load->library('RDF_Parser');
		$this->ci->load->model('Survey_resource_model');

		//parse RDF to array
		$rdf_array=$this->ci->rdf_parser->parse($rdf_contents);

		if ($rdf_array===FALSE || $rdf_array==NULL)
		{
			return FALSE;
		}

		//Import
		$rdf_fields=$this->ci->rdf_parser->fields;

		//success
		foreach($rdf_array as $rdf_rec)
		{
			$insert_data['survey_id']=$surveyid;

			foreach($rdf_fields as $key=>$value)
			{
				if ( isset($rdf_rec[$rdf_fields[$key]]))
				{
					$insert_data[$key]=trim($rdf_rec[$rdf_fields[$key]]);
				}
			}

			//check filenam is URL?
			if (!is_url($insert_data['filename']))
			{
				//clean file paths
				$insert_data['filename']=unix_path($insert_data['filename']);

				//remove slash before the file path otherwise can't link the path to the file
				if (substr($insert_data['filename'],1,1)=='/')
				{
					$insert_data['filename']=substr($insert_data['filename'],2,255);
				}
			}

			//check if the resource file already exists
			//$resource_exists=$this->ci->Survey_resource_model->get_survey_resources_by_filepath($insert_data['filename']);

			//insert into db
			$this->ci->Survey_resource_model->insert($insert_data);
		}
	}


	/**
	*
	* Returns array of warnings for a single study
	**/
	function get_study_warnings($sid)
	{
		$this->ci->load->model('Survey_resource_model');
		$this->ci->load->model('Catalog_model');
		$warnings=array();

		//published
		//collection dates are missing?
		$this->ci->db->select('published,year_start,year_end');
		$this->ci->db->where('id',$sid);
		$study_row=$this->ci->db->get('surveys')->row_array();

		if($study_row['published']==0)
		{
			$warnings[]='warning_study_not_published';
		}

		if ((int)$study_row['year_start']===0 && (int)$study_row['year_end']===0)
		{
			$warnings[]='warning_study_years_not_set';
		}

		//study data access model
		//$study_da_model=$this->ci->Catalog_model->get_survey_form_model($sid);

		//get study resources count  grouped by resource type
		$resources=$this->ci->Survey_resource_model->get_grouped_resources_count($sid);

		if (!$resources)
		{
			$warnings[]='warning_study_has_no_external_resources';
		}

		//pdf documentation?
		$has_pdf=$this->get_study_pdf($sid);
		if($has_pdf['status']=='na')
		{
			$warnings[]='warning_study_has_no_pdf_documentation';
		}

		//pending requests
		$this->ci->load->model("licensed_model");

		$pending_requests=$this->ci->licensed_model->get_pending_requests_count($sid);

		if ($pending_requests>0)
		{
			$warnings[]='warning_study_has_pending_licensed_requests';
		}

		return $warnings;
	}


	/**
	*
	* Fix file paths for external resources
	**/
	function fix_resource_links($surveyid)
	{
		$this->ci->load->model('Catalog_model');
		$this->ci->load->model('Survey_resource_model');
		$this->ci->load->model('Managefiles_model');

		//get survey folder path
		$survey_folder=$this->ci->Catalog_model->get_survey_path_full($surveyid);

		//get survey resources
		$resources=$this->ci->Survey_resource_model->get_survey_resource_files($surveyid);

		//hold broken resources
		$broken_links=array();

		//build an array of broken resources, ignore the resources with correct paths
		foreach($resources as $resource)
		{
			//check if the resource file found on disk
			if(!is_url($resource['filename']))
			{
				if(!file_exists( unix_path($survey_folder.'/'.$resource['filename'])))
				{
					$broken_links[]=$resource;
				}
			}
		}

		//get a list of all files in the survey folder
		$files=$this->ci->Managefiles_model->get_files_recursive($survey_folder,$survey_folder);

		//number of links fixed
		$fixed_count=0;

		//find matching files in the filesystem for the broken links
		foreach($broken_links as $key=>$resource)
		{
			$match=FALSE;

			//search files array and return the relative path to the file if found
			foreach($files['files'] as $file)
			{
				//match found
				if(strtolower($file['name'])==strtolower(basename($resource['filename'])) )
				{
					$match=$file['relative'];

					//update path in database
					$this->ci->Survey_resource_model->update($resource['resource_id'],array('filename'=>$file['relative'].'/'.$file['name']));

					//update the count
					$fixed_count++;

					break;
				}
			}

			//add path for the resources
			$broken_links[$key]['match']=$match;
		}

		return $fixed_count;
	}

	function replace_ddi($sid,$new_ddi_file)
	{
		$this->ci->load->model("Survey_alias_model");
		$this->ci->load->model("Dataset_model");
		//$this->ci->load->model("Data_file_model");
		$this->ci->load->library('Dataset_manager');

		//get survey info
		$survey=$this->ci->Dataset_model->get_row($sid);
		$user=$this->ci->ion_auth->current_user();

		if (!$survey){
			throw new Exception("SURVEY_NOT_FOUND");
		}

		require_once dirname(__FILE__).'/Metadata_parser/classes/DDI2Reader.php';
        require_once dirname(__FILE__).'/Metadata_parser/classes/DdiVariableIterator.php';
        
		$this->ddi2reader= new DDI2Reader($new_ddi_file);
		$new_idno=$this->ddi2reader->get_study_IDNO();

		$this->ddi2reader=null;
		 
		 //sanitize ID to remove anything except a-Z1-9 characters
		 /*if ($new_idno!==$this->sanitize_filename($new_idno)){
			 throw new Exception(t('IDNO_INVALID_FORMAT').': '.$new_idno);
		 }*/
 
		 //check if the study already exists, find the sid		
		$new_ddi_sid=$this->ci->dataset_manager->find_by_idno($new_idno);

		//check if uploaded study ID is used by another study in the catalog
		if(!empty($new_ddi_sid) && $new_ddi_sid!=$sid){			
			$error=t('replace_ddi_failed_duplicate_study_found'). ': '.anchor(site_url('admin/catalog/edit/'.$new_ddi_sid));
			$this->db_logger->write_log('ddi-replace-error',$error,'catalog');
			throw new Exception($error);
		}

		//copy
		$survey_folder_path=$this->ci->Dataset_model->get_storage_fullpath($sid);
		$survey_target_ddi=unix_path($survey_folder_path.'/'.$new_idno.'.xml');

		if (!@rename($new_ddi_file,$survey_target_ddi)){
			throw new Exception("COPY_FAILED: ".$survey_target_ddi);
		}

		//update survey metadata to point to new file
		$survey_options=array(
			'metafile'=>$new_idno.'.xml'
		);
		
		$this->ci->Dataset_model->update_options($sid,$survey_options);

		//add aliases
		$this->ci->Survey_alias_model->upsert($sid,$survey['idno']);
		$this->ci->Survey_alias_model->upsert($sid,$new_idno);

		return $survey_target_ddi;
	}


}//end class

