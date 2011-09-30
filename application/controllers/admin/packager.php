<?php
class Packager extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
       	$this->load->model('Packager_model');
		$this->template->set_template('admin');
		
		$this->lang->load("general");
		$this->lang->load("dashboard");
		
		//package folder
		$this->package_folder='backup/packages';
    }
 
	function index()
	{	
		$data['title']='Dashboard';
		
		//load the contents of the page into a variable
		$content="Studies packager";
		
		//set page title
		$this->template->write('title', 'data packager',true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	
	//create package
	function create()
	{
		//check requiremnets
		$this->_check_requirements();
	
		$surveys=$this->Packager_model->get_surveys();
		$output=array();
		foreach($surveys as $survey)
		{
			//get survey external resources
			$resources=$this->Packager_model->get_resources($survey['id']);
			
			$survey_obj=(object)$survey;			
			$survey_obj->resources=$resources;
			
			$output[]=$survey_obj;
		}

		//package file
		$this->package_folder='backup/packages';
		$file_name='/package-'.date("m-d-y-His").'.pkg';
		$file_path=$this->package_folder.$file_name;
		
		//save package contents
		$result=@file_put_contents($file_path, json_encode($output));
		
		if (!$result)
		{
			show_error("FAILED_TO_WRITE_TO_PACAKE_FILE");
		}
		
		echo $file_name.' created successfully!<BR>';
		echo count($output). ' studies were packaged';

	}
	
	//restore package
	function restore()
	{
		$filename='package-09-28-11-125529.pkg';
		$file_path=$this->package_folder.'/'.$filename;
		
		if (!file_exists($file_path))
		{
			show_error('FILE_NOT_FOUND');
		}
		
		//read file contents
		$contents=file_get_contents($file_path);
		
		//decode json file		
		$data=json_decode($contents);
		
		if (!$data)
		{
			show_error("INVALID_PACKAGE_FILE");
		}
		
		set_time_limit(0);
		echo count($data);
		//$k=0;
		foreach($data as $survey)
		{
			$result=$this->_restore_single($survey);
			/*if ($result)
			{
				$k++;
			}
			
			if($k>=1)
			{
				return TRUE;
			}*/
		}
	}
	
	//restore a single study from package
	function _restore_single($survey_obj)
	{
		set_time_limit(0);
		//echo '<pre>';
		//print_r($survey_obj);
		//exit;
				
		//check if study already exists in the catalog by matching surveyid field
		$exists=$this->Packager_model->study_exists($survey_obj->surveyid);
		
		if ($exists!==FALSE)
		{
			//show_error("STUDY_ALREADY_EXISTS");
			echo 'skipped >> STUDY_ALREADY_EXISTS >> '.$survey_obj->surveyid."<BR>\r\n";
			return FALSE;
		}
		
		//------------------------------------------------------------//
		// build paths
		//------------------------------------------------------------//
		
		//get datasets folder path
		$catalog_root=$this->config->item("catalog_root");

		//survey folder path
		$survey_folder=$catalog_root.'/'.$survey_obj->dirpath;		
		$survey_folder=str_replace('\\','/',$survey_folder);
		$survey_folder=str_replace('//','/',$survey_folder);
		
		//survey ddi path
		$ddi_path=$survey_folder.'/'.$survey_obj->ddifilename;
		
		//check if survey folder and ddi is accessible
		if (!file_exists($survey_folder))
		{
			show_error("SURVEY_FOLDER_NOT_FOUND".$survey_folder);
		}
		
		if (!file_exists($ddi_path))
		{
			show_error("DDI_FILE_NOT_FOUND");
		}
		
		//----------------------------------------------/
		// import study
		//----------------------------------------------/
		echo 'importing study >> '.$survey_obj->surveyid.' - '.$survey_obj->varcount."<BR>\r\n";
		
		//load DDI Parser Library
		$this->load->library('DDI_Parser');
		$this->load->library('DDI_Import','','DDI_Import');

		//set file for parsing
		$this->ddi_parser->ddi_file=$ddi_path;
		
		//use xml_reader for parsing xml
		$this->ddi_parser->use_xml_reader=TRUE;
		
		//validate DDI file
		if ($this->ddi_parser->validate()===false)
		{
			$error= 'Invalid DDI file: '.$ddi_path;
			log_message('error', $error);

			show_error($error);
		}
						
		//parse ddi
		$data=$this->ddi_parser->parse();

		//set the repository where the ddi will be uploaded to
		$this->DDI_Import->repository_identifier='default';//////////////////////
		
		//import ddi to db
		$result=$this->DDI_Import->import($data,$ddi_path,$overwrite=TRUE);		
		
		//import success
		if ($result==FALSE)
		{
			show_error("STUDY_IMPORT_FAILED ".$survey_obj->surveyid);
		}
		
		//update survey options
		$this->Packager_model->update_study_options($survey_obj);
		
		//import external resources for the study
		$this->Packager_model->import_resources($survey_obj->surveyid,$survey_obj->resources);
		
		return TRUE;
	}
	
	
	//test packager requirements
	function _check_requirements()
	{
		if (!file_exists($this->package_folder))
		{
			@mkdir('backup/packages');
		}
		
		if (!file_exists($this->package_folder))
		{
			show_error('Package folder not found. Create a folder backup/packages and run packager again.');
		}	
	}
}
/* End of file packager.php */
/* Location: ./controllers/admin/packager.php */