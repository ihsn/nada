<?php

/**
* Manage study files & data access
*
*
*/
class Managefiles extends MY_Controller {
	
    function __construct()
    {
        parent::MY_Controller();
        $this->template->set_template('blank');
		$this->load->helper('file');		
		$this->load->model("managefiles_model");
		$this->load->model("resource_model");
		$this->load->model("form_model");
		$this->load->model("catalog_model");
		
		$this->lang->load("general");
		$this->lang->load("resource_manager");
		//$this->output->enable_profiler(TRUE);
    }
	
	/*
	* Show all files by surveyid
	*
	*/
	function index($surveyid=NULL)
	{
		if (!is_numeric($surveyid))
		{
			show_error('INVALID_ID');
		}
		
		//get survey folder path
		$folderpath=$this->managefiles_model->get_survey_path($surveyid);
		
		//name of the file to lock for deletion
		$this->ddi_file_name=basename($this->catalog_model->get_survey_ddi_path($surveyid));
		
		//process file upload if any
		$this->_process_uploads($folderpath);
		
		//process folder create (if any)
		$this->_create_folder($folderpath);
		
		//process delete files
		$this->_delete_files($folderpath);
		
		//get all survey files
		$data=$this->managefiles_model->get_files_recursive($folderpath,$folderpath);
		
		//array of folders and subfolders	
		$this->folders=$data['dirs'];
		
		//get array of resources to check which files are linked
		$this->resources=$this->managefiles_model->get_resource_paths_array($surveyid);

		//match resources with file path
		foreach($data["files"] as $key=>$file){
			$data["files"][$key]["resource"]=$this->_match_resource_paths($this->resources,$file);
		}

		//show listing
		$content=$this->load->view("managefiles/index",$data,TRUE);
				
		//render page
		$this->template->write('content', $content,true);
		$this->template->write('title', t('resource_manager'),true);
	  	$this->template->render();	
	}

	/*
	* Show files by surveyid using a folder view
	*
	*/
	function folder_view($surveyid=NULL)
	{
		//get survey folder path
		$survey_folder=$this->managefiles_model->get_survey_path($surveyid);
		
		//name of the file to lock for deletion
		$this->ddi_file_name=basename($this->catalog_model->get_survey_ddi_path($surveyid));

		//build path for the folder to show files
		$current_folder=$survey_folder;
		
		//get folder name from querystring
		if ($this->input->get("folder"))
		{
			$current_folder.='/'.$this->input->get("folder");
			$current_folder=unix_realpath($current_folder);
			
			//if accessing a folder outside the survey folder
			if (strpos($current_folder,$survey_folder)===FALSE)
			{
				show_404();
			}
		}
			
		//process file upload if any
		$this->_process_uploads($current_folder);
		
		//process folder create (if any)
		$this->_create_folder($current_folder);
		
		//process delete files
		$this->_delete_files($current_folder);
		
		//get all survey files
		$data=$this->managefiles_model->get_files_non_recursive($current_folder,$current_folder);
		
		//to pass to view
		$data['current_folder']=($this->input->get("folder")!==FALSE) ? $this->input->get("folder") : '';
		
		//array of folders and subfolders	
		$this->folders=$data;
		
		//get array of resources to check which files are linked
		$this->resources=$this->managefiles_model->get_resource_paths_array($surveyid);

		//match resources with file path
		foreach($data["files"] as $key=>$file){
			$data["files"][$key]["resource"]=$this->_match_resource_paths($this->resources,$file);
		}

		//show listing
		$content=$this->load->view("managefiles/folder_view",$data,TRUE);
				
		//render page
		$this->template->write('content', $content,true);
		$this->template->write('title', t('resource_manager'),true);
	  	$this->template->render();	
	}
	

	function _match_resource_paths($resources_array,&$file)
	{
		if (is_array($resources_array)){
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
    
	function edit($surveyid, $filepath)
	{		
		$filepath=$this->_clean_filepath(urldecode(base64_decode($filepath)));
		
		//check if a resource matches the filepath
		$resource=$this->managefiles_model->get_resources_by_filepath($surveyid,$filepath);
		
		$data['page_title']=t('edit_resource');
		$data['filename']=$filepath;
		
		$content=NULL;
		
		//validation rules
		$this->form_validation->set_rules('title', t('title'), 'trim|required|max_length[255]');
		
		//process form				
		if ($this->form_validation->run() == TRUE)
		{
			$options=array();
			$post_arr=$_POST;
			
			$resource_id=$this->input->post("resource_id");
						
			//read post values to pass to db
			foreach($post_arr as $key=>$value)
			{
				$options[$key]=$this->input->post($key);
			}
			
			$options['survey_id']=$this->uri->segment(3);

			if (!is_numeric($resource_id))
			{
				//insert
				$db_result=$this->resource_model->insert($options);
			}
			else
			{
				//update db
				$db_result=$this->resource_model->update($resource_id,$options);
			}
						
			if ($db_result===TRUE)
			{
				//update successful
				$this->session->set_flashdata('message', t('form_update_success'));
				
				//redirect back to the list
				redirect("admin/managefiles/".$surveyid,"refresh");
			}
			else
			{
				//update failed
				$this->form_validation->set_error(t('form_update_fail'));
			}
		}
		else //loading form the first time
		{
			//if resource found, show edit form
			if ($resource)
			{
				$data=array_merge($data,$resource[0]);			
			}
		}
		
		//resource form
		$content=$this->load->view('managefiles/edit_resource',$data,TRUE);		
			
		//render page
		$this->template->write('content', $content,true);
		$this->template->write('title', $data['page_title'],true);
	  	$this->template->render();		
	}
	
	function _clean_filepath($str)
	{
		$str=unix_path($str);
		
		if (substr($str,0,1)=='/')
		{
			$str=substr($str,1,strlen($str));
		}
		return $str;
	}
	
	function add()
	{
		$this->edit(NULL);
	}
		
	
	/**
	*
	* Let user select the Access Method (public,direct, etc)
	*
	*/
	function data_access($surveyid)
	{	
		//update the form if user has changed the selection
		$this->_update_form($surveyid);		
		
		try
		{
			//get the survey active access form
			$selected_form=$this->managefiles_model->get_survey_access_form($surveyid);
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
			return FALSE;
		}
				
		//drop down list options for selecting the form
		$this->form_list=$this->form_model->get_form_list();
				
		//drop down form
		$content=$this->load->view("managefiles/select_form",$selected_form,TRUE);
		
		//get survey micro data files (exteranl resources)
		$data['files']=$this->managefiles_model->get_data_files($surveyid);
			
		//show the data files
		$content.=$this->load->view("managefiles/datafiles",$data,TRUE);
		
		
		//render page
		$this->template->write('content', $content,true);
		$this->template->write('title', t('select_data_access_type'),true);
	  	$this->template->render();	
	}

	
	
	function _update_form($surveyid)
	{
		//update the form
		$formid=$this->input->post("formid");	
		
		//check if need to change form selection
		if (!is_numeric($formid))
		{
			return FALSE;
		}			
		
		//update form selection in db
		$this->managefiles_model->update_form($surveyid,$formid);
		
		//get request form model information
		$forminfo=$this->managefiles_model->get_survey_access_form($surveyid);

		//set help message for the user indicating what each form model provides
		$this->form_message=$this->load->view('managefiles/form_message',array('model'=>$forminfo['model']),TRUE);
	}
	
	
    function _remap()
    {
		switch($this->uri->segment(4))
		{
			case 'add':
				$this->add();
			break;
			
			case 'edit':
				$this->edit($this->uri->segment(3),$this->uri->segment(5));
			break;

			case 'download':
				$this->download($this->uri->segment(3),$this->uri->segment(5));
			break;

			case 'delete':
				$this->delete($this->uri->segment(3),$this->uri->segment(5));
			break;

			case 'access':
				$this->data_access($this->uri->segment(3));
			break;	
					
			case 'resources':
				$this->resources($this->uri->segment(3));
			break;	
		
			default:
				
				//view info from session
				$view=$this->session->userdata('folder_view');
				
				if ($this->input->get("view")=='folder')
				{
					//update session cookie
					$this->session->set_userdata( array('folder_view'=>'folder'));
					
					//show folder view
					$this->folder_view($this->uri->segment(3));					
				}
				else if ($this->input->get("view")=='simple')
				{
					//remove session, to return to default non-folder view
					$this->session->unset_userdata('folder_view');
					
					//default view
					$this->index($this->uri->segment(3));
				}
				else if ( $view!==false && $view=='folder')
				{
					//show folder view
					$this->folder_view($this->uri->segment(3));
				}
				else
				{						
					//default non-folder view					
					$this->index($this->uri->segment(3));
				}	
		}		                 
    }


	function _create_folder($absolute_path)
	{
		if (!$this->input->post('create') )
		{
			return;
		}
	
		$name=$this->input->post('name');
		$type=$this->input->post('type');
			
		if ($type=='dir')
		{
			//Create folder
			$rs = @mkdir( $absolute_path.'/'.$name, 0777 ); 
			if( !$rs ) {
				$this->errors[]=t('create_folder_failed').$name ;
			} 
		}
	}


	/**
	*
	* Upload files
	*
	*/
	function _process_uploads($absolute_path)
	{
		if (!$this->input->post('upload') )
		{
			return;
		}
		
		$target_folder=$this->input->post("upload_folder");
		
		if($target_folder!='')
		{
			$absolute_path=unix_path($absolute_path.'/'.$target_folder);
		}
		
		if (!file_exists($absolute_path))
		{
			$this->errors[]=t('folder_not_found'). $absolute_path;
			return FALSE;
		}
		
		$files=$_FILES;
		
		if (!$files)
		{
			return;
		}
		
		foreach ($files["file"]["error"] as $key => $error) 
		{
		    if ($error == UPLOAD_ERR_OK) 
			{
		        $tmp_name = $_FILES["file"]["tmp_name"][$key];
        		$name = $_FILES["file"]["name"][$key];
				if (!file_exists("$absolute_path/$name"))
				{
		        	move_uploaded_file($tmp_name, "$absolute_path/$name");
				}
				else
				{
					$this->errors[]='File already exists: '. $name;
				}	
    		}
		}	
	}



	/*
	* Download a file
	*
	*/
	function download($surveyid, $base64_filepath)
	{
		if(!is_numeric($surveyid))
		{
			show_error('Invalid parameters supplied');
		}

		//get survey folder path
		$folderpath=$this->managefiles_model->get_survey_path($surveyid);
		
		$filepath=$this->_clean_filepath(urldecode(base64_decode($base64_filepath)));
		
		$fullpath=unix_path($folderpath.'/'.$filepath);
		
		//log deletion
		$this->db_logger->write_log('download',$fullpath,'external-resource');
		
		if (is_dir($fullpath))
		{
			return false;
		}
		else if (is_file($fullpath))
		{
			//download file
			$this->load->helper('download');
			log_message('info','Downloading file <em>'.$fullpath.'</em>');
			force_download2($fullpath);
		}
	}

	/*
	* Delete a single file
	*
	*/
	function delete($surveyid, $base64_filepath)
	{
		if(!is_numeric($surveyid))
		{
			show_error('Invalid parameters supplied');
		}

		//get survey folder path
		$folderpath=$this->managefiles_model->get_survey_path($surveyid);
		
		$filepath=$this->_clean_filepath(urldecode(base64_decode($base64_filepath)));
		
		$fullpath=unix_path($folderpath.'/'.$filepath);
		
		//log deletion
		$this->db_logger->write_log('delete',$fullpath,'external-resource');
		
		if (is_dir($fullpath))
		{
			$isdeleted=$this->delete_folder($fullpath);
		}
		else if (is_file($fullpath))
		{
			$isdeleted=silent_unlink($fullpath);
			
			if($isdeleted===FALSE)
			{
				$this->session->set_flashdata('error', t('file_delete_failed'));
			}
			else
			{
				$this->session->set_flashdata('message', t('file_delete_success'));
			}
		}
		
		//redirect
		redirect("admin/managefiles/".$surveyid,"refresh");
	}
	
	function _delete_files($absolute_path)
	{
		if (!$this->input->post('delete') )
		{
			return;
		}
		
		$filenames=$this->input->post('filename');

		if ($filenames)
		{
			foreach($filenames as $file)
			{
				$file=trim($file);
				
				if ($file!='..' && $file!='.' )
				{
					$filepath=unix_path($absolute_path.'/'.$file);
					
					if(!file_exists($filepath))
					{
						$this->errors[]=t('folder_not_found'). $filepath;
					}
					else
					{
						//log deletion
						$this->db_logger->write_log('delete',$filepath,'external-resource');
					
						if (is_dir($filepath))
						{							
							$isremoved=$this->delete_folder($filepath);
						}
						else
						{
							$isremoved=silent_unlink($filepath);
						}
						
						if($isremoved===FALSE)
						{
							$this->errors[]=t('file_delete_failed'). $filepath;
						}
					}	
				}
			}
		}
	}
	
	/**
	* Removes a folder, subfolder and all files
	*
	* @author: asn at asn24 dot dk
	* @link: http://php.net/manual/en/function.rmdir.php
	*/
	function delete_folder($dir) 
	{ 
		if (!file_exists($dir)) return true; 
		if (!is_dir($dir) || is_link($dir)) return unlink($dir); 
			foreach (scandir($dir) as $item) { 
				if ($item == '.' || $item == '..') continue; 
				if (!$this->delete_folder($dir . "/" . $item)) { 
					chmod($dir . "/" . $item, 0777); 
					if (!$this->delete_folder($dir . "/" . $item)) return false; 
				}; 
			} 
			return rmdir($dir); 
    } 
	
}