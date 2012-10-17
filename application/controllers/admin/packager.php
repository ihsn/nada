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


	//create package by repositoryid
	function create_by_repo($repoid)
	{
		//check requiremnets
		$this->_check_requirements();
	
		$surveys=$this->Packager_model->get_surveys_by_repo($repoid);
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



	//create package
	function create_selective()
	{
		//check requiremnets
		$this->_check_requirements();
			
		$selected=$this->input->post("id");		
		$selected=explode("\r",$selected);
		$json_output=NULL;
		
		//trim survey id
		foreach($selected as $key=>$value)
		{
			$selected[$key]=trim($value);
		}
		
		if (is_array($selected) && count($selected)>0)
		{		
			$surveys=$this->Packager_model->get_surveys($selected);
			
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
			$file_name='/package-'.date("m-d-y-His").'.pkg';
			$file_path=$this->package_folder.$file_name;
			
			$json_output=json_encode($output);
			
			//save package contents
			$result=@file_put_contents($file_path, $json_output);
			
			if (!$result)
			{
				show_error("FAILED_TO_WRITE_TO_PACAKE_FILE");
			}
			
			echo $file_name.' created successfully!<BR>';
			echo count($output). ' studies were packaged';
		}
		
		$this->load->view("packager/selective",array('json_output'=>$json_output));
	}
	
	
	//package a single study
	function package_study($studyid=NULL)
	{
		if (!is_numeric($study))
		{
			show_error("INVALID_STUDY");
		}
		
		/*
			Tasks
			
			1. copy DDI
			2. create RDF
			3. copy files defined in RDF
			4. 
		*/
		
	}
	
	
	
	//package users to json
	function package_users()
	{
		$this->db->select('*');
		$users=$this->db->get("shared_db.users")->result_array();

		$this->db->select('*');
		$users_meta=$this->db->get("shared_db.meta")->result_array();
		
		$output=array();
		foreach($users as $user)
		{
			$output[$user['id']]=$user;
		}
		
		foreach($users_meta as $meta)
		{
			if (isset($output[$meta['user_id']]))
			{
				$output[$meta['user_id']]['meta']=$meta;
			}
		}
		
		/*$output=array(
				'users'=>$users,
				'meta'=>$users_meta);
		*/
		
		$file_name='/users-'.date("m-d-y-His").'.pkg';
		$file_path=$this->package_folder.$file_name;
		
		//save package contents
		$result=@file_put_contents($file_path, json_encode($output));
		
		if (!$result)
		{
			show_error("FAILED_TO_WRITE_TO_PACAKE_FILE");
		}
		
		echo $file_name.' created successfully!<BR>';
	}




	//update harvester_queue table with study origin info
	function update_harvester_queue($package_file=NULL)
	{
		$file_name=basename($package_file);
		$file_path=$this->package_folder.'/'.$file_name;
		
		if (!file_exists($file_path))
		{
			show_404();
		}
		
		//read file contents
		$contents=file_get_contents($file_path);
		
		//decode json file		
		$data=json_decode($contents);
		
		if (!$data)
		{
			show_error("INVALID_PACKAGE_FILE");
		}

		foreach($data as $survey)
		{
			$this->Packager_model->update_harvester_queue($survey);
		}

	}




	//restore users from package file
	function restore_users($package_file=NULL)
	{
		$file_name=basename($package_file);
		$file_path=$this->package_folder.'/'.$file_name;
		
		if (!file_exists($file_path))
		{
			show_404();
		}
		
		//read file contents
		$contents=file_get_contents($file_path);
		
		//decode json file		
		$data=json_decode($contents);
		
		if (!$data)
		{
			show_error("INVALID_PACKAGE_FILE");
		}
		
		//import users
		$this->Packager_model->import_users($data);
		
		echo 'done';
	}	




	/**
	*
	* Generate file copy script to copy the survey DDI files
	**/
	function generate_copy_script()
	{
		//check requiremnets
		$this->_check_requirements();
	
		$surveys=$this->Packager_model->get_surveys();
		$output=array();

		//package file
		$file_name='/copy-script-'.date("m-d-y-His").'.pkg';
		$file_path=$this->package_folder.$file_name;
		
		$catalog_root=$this->config->item("catalog_root");
		
		//build an array with path to the survey ddi file
		foreach($surveys as $row)
		{
			$output='';
			$ddi_path=$catalog_root.'/'.$row['dirpath'].'/'.$row['ddifilename'];
			$output='[COPY] '.$ddi_path.' [DESTINATION_FOLDER]'."\r";
		
			//write to file
			$result=@file_put_contents($file_path, $output,FILE_APPEND);
			
			if (!$result)
			{
				show_error("FAILED_TO_WRITE_TO_PACAKE_FILE");
			}

		}
				
		echo $file_name.' created successfully!<BR>';
		echo count($surveys). ' studies were packaged';
	}


	//Generate RDF files for each study from the packaged JSON file
	//TODO: not completed
	function generate_rdf_from_package($package_file=NULL)
	{
		$file_name=basename($package_file);
		$file_path=$this->package_folder.'/'.$file_name;
		
		if (!file_exists($file_path))
		{
			show_404();
		}
		
		//read file contents
		$contents=file_get_contents($file_path);
		
		//decode json file		
		$data=json_decode($contents);
		
		if (!$data)
		{
			show_error("INVALID_PACKAGE_FILE");
		}
		
		foreach($data as $survey)
		{
			var_dump($survey);exit;
		}
		
	}

	function restore_resources($package_file=NULL)
	{
		$file_name=basename($package_file);
		$file_path=$this->package_folder.'/'.$file_name;
		
		if (!file_exists($file_path))
		{
			show_404();
		}
		
		//read file contents
		$contents=file_get_contents($file_path);
		
		//decode json file		
		$data=json_decode($contents);
		
		if (!$data)
		{
			show_error("INVALID_PACKAGE_FILE");
		}
		
		foreach($data as $survey)
		{
			$this->_restore_study_resources($survey);
		}
	
	}
	
	
	function restore_survey_options($package_file=NULL)
	{
		$file_name=basename($package_file);
		$file_path=$this->package_folder.'/'.$file_name;
		
		if (!file_exists($file_path))
		{
			show_404();
		}
		
		//read file contents
		$contents=file_get_contents($file_path);
		
		//decode json file		
		$data=json_decode($contents);
		
		if (!$data)
		{
			show_error("INVALID_PACKAGE_FILE");
		}
		
		foreach($data as $survey)
		{
			//var_dump($survey);
			//update survey options
			$this->Packager_model->update_study_options($survey);
			
		}
	
	}
	

	//restore resources for a single study
	function _restore_study_resources($survey_obj)
	{
		//check if study already exists in the catalog by matching surveyid field
		$exists=$this->Packager_model->study_exists($survey_obj->surveyid);
		
		if (!$exists)
		{
			//show_error("STUDY_ALREADY_EXISTS");
			echo 'skipped >> STUDY_NOT_FOUND >> '.$survey_obj->surveyid."<BR>\r\n";
		}

		//remove any existing resources for the study
		$this->Packager_model->delete_resources($survey_obj->surveyid);
		
		//import external resources for the study
		$this->Packager_model->import_resources($survey_obj->surveyid,$survey_obj->resources);
		
		echo 'Imported >> STUDY_FOUND >> '.$survey_obj->surveyid."<BR>\r\n";
		return TRUE;
	}
	
	
	
	//restore package
	function restore($package_file)
	{
		$filename=$package_file;//'package-09-28-11-125529.pkg';
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
		$survey_folder=unix_path($catalog_root.'/'.$survey_obj->dirpath);		
		
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
		$this->DDI_Import->repository_identifier=$survey_obj->repositoryid;//////////////////////
		
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