<?php

/**
* Manage study files & data access
*
*
*/
class Managefiles extends MY_Controller {
	
    function __construct()
    {
		parent::__construct();		
        $this->template->set_template('admin');
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
		
		//test user study permissiosn
		$this->acl->user_has_study_access($surveyid);
		
		//get survey folder path
		$folderpath=$this->managefiles_model->get_survey_path($surveyid);

		$this->survey_folder=$folderpath;
		
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
		$survey_folder=unix_realpath($this->managefiles_model->get_survey_path($surveyid));
		
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
				redirect("admin/catalog/edit/".$surveyid,"refresh");
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
		try
		{
			//get the survey active access form 
			$selected_form_obj=$this->managefiles_model->get_survey_access_form($surveyid);
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
			return FALSE;
		}
		
		//update the form if user has changed the selection
		$this->_update_form($surveyid,$selected_form_obj);
		
		//drop down list options for selecting the form
		$this->form_list=$this->form_model->get_form_list();
				
		//drop down form
		$content=$this->load->view("managefiles/select_form",$selected_form_obj,TRUE);
		
		//get survey micro data files (exteranl resources)
		$data['files']=$this->managefiles_model->get_data_files($surveyid);
			
		//show the data files
		$content.=$this->load->view("managefiles/datafiles",$data,TRUE);	
		
		//render page
		$this->template->write('content', $content,true);
		$this->template->write('title', t('select_data_access_type'),true);
	  	$this->template->render();	
	}

	
	/**
	*
	* Attach form to a survey
	*/
	function _update_form($surveyid)
	{
		//update the form
		$formid=$this->input->post("formid");	
		
		//update form selection in db
		if (is_numeric($formid))
		{			
			$this->managefiles_model->update_form($surveyid,$formid);
		}			
				
		//get request form model information
		$forminfo=$this->managefiles_model->get_survey_access_form($surveyid);
		
		//to be passed to view
		$data=array('model'=>$forminfo['model']);

		//update remote form url
		if ($forminfo['model']=='remote')
		{
			$this->form_validation->set_rules('link_da', t('url'), 'prep_url|xss_clean|trim|valid_url|callback__url_check|max_length[255]');
			
			$form_validated=$this->form_validation->run();
			if ($form_validated == TRUE && $this->input->post("link_da")!==FALSE)
			{
				$link_da=$this->input->post("link_da");				
				$options=array(
						'id'=>$surveyid,
						'link_da'=>$link_da);				
				//add to db
				$this->catalog_model->update_survey_options($options);
			}
			else
			{
				//only when the form is loaded the first tiem
				if(!isset($link_da))
				{
					//get the remote link if already set in db
					$survey_row=$this->catalog_model->select_single($surveyid);
					$data['link_da']=$survey_row['link_da'];
				}
			}
		}
				
		//set help message for the user indicating what each form model provides
		$this->form_message=$this->load->view('managefiles/form_message',$data,TRUE);
	}
	
	
	
    function _remap()
    {
		switch($this->uri->segment(4))
		{
			case 'add':
				$this->add();
			break;
			
			case 'plupload':
				$this->plupload();
			break;
			
			case 'process_batch_uploads':
				$this->process_batch_uploads();
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

			case 'batch_delete':
				$this->batch_delete($this->uri->segment(3));
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

	function plupload()
	{
		$this->load->view("managefiles/plupload");
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
	
	function batch_delete($surveyid)
	{
		$files=(array)$this->input->post("filename");
		foreach($files as $file)
		{
			$this->delete($surveyid,$file);
		}
		
		//redirect
		redirect("admin/catalog/edit/".$surveyid,"refresh");
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

		//isajax?
		$ajax=$this->input->get_post('ajax');

		//get survey folder path
		$folderpath=$this->managefiles_model->get_survey_path($surveyid);
		
		$filepath=$this->_clean_filepath(urldecode(base64_decode($base64_filepath)));
		
		$fullpath=unix_path($folderpath.'/'.$filepath);
		
		//log deletion
		$this->db_logger->write_log('resource-delete',$fullpath,'external-resource',$surveyid);
		
		if (is_dir($fullpath))
		{
			$isdeleted=$this->delete_folder($fullpath);
		}
		else if (is_file($fullpath))
		{
			$isdeleted=silent_unlink($fullpath);
			
			if($isdeleted===FALSE)
			{
				if ($ajax==1)
				{
					echo ('file_delete_failed');return;
				}

				$this->session->set_flashdata('error', t('file_delete_failed'));
			}
			else
			{
				if ($ajax==1)
				{
					echo ('file_delete_success');return;
				}

				$this->session->set_flashdata('message', t('file_delete_success'));
			}
		}
				
		//redirect
		redirect("admin/catalog/edit/".$surveyid."/files","refresh");
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
		$this->db_logger->write_log('delete-folder',$dir,'external-resource');
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
	
	
	 /**
	 * Upload multiple files using plupload
	 *
	 * Based on the code by Moxiecode Systems AB
	 * Released under GPL License.
	 *
	 * License: http://www.plupload.com/license
	 * Contributing: http://www.plupload.com/contributing
	 */
	function process_batch_uploads()
	{
		$surveyid=(int)$this->uri->segment(3);
		$overwrite=$this->input->post("overwrite");
		
		if ($overwrite!=1)
		{
			$overwrite=0;
		}
		
		if (!is_numeric($surveyid))
		{
			return FALSE;
		}
		
		
		$survey_path=$this->managefiles_model->get_survey_path($surveyid);
		
		if ($survey_path=='' || !file_exists($survey_path))
		{
			show_404();
		}
		
		//test user study permissiosn
		$this->acl->user_has_study_access($surveyid);
		
		//$resource_folder=$this->input->post("upload_folder");
		$resource_folder=unix_path($survey_path);
		
		if (!file_exists($resource_folder))
		{
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Resource folder does not exist"}, "id" : "'.$resource_folder.'"}');
		}
		
		// HTTP headers for no cache etc
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		
		// Settings
		//$targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
		$targetDir = $resource_folder;
		//$targetDir=APPPATH.'../plupload/'.$this->input->post("upload_folder");
		
		//$cleanupTargetDir = false; // Remove old files
		//$maxFileAge = 60 * 60; // Temp file age in seconds
		
		// 5 minutes execution time
		@set_time_limit(15 * 60);
		
		// Uncomment this one to fake upload time
		// usleep(5000);
		
		// Get parameters
		$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
		$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
		$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
		
		// Clean the fileName for security reasons
		//$fileName = preg_replace('/[^\w\._]+/', '', $fileName);
		
		// Make sure the fileName is unique but only if chunking is disabled
		if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
			$ext = strrpos($fileName, '.');
			$fileName_a = substr($fileName, 0, $ext);
			$fileName_b = substr($fileName, $ext);
		
			$count = 1;
			while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
				$count++;
		
			$fileName = $fileName_a . '_' . $count . $fileName_b;
		}
		
		// Create target dir
		if (!file_exists($targetDir))
		{
			@mkdir($targetDir);
		}
		
		
		// Remove old temp files
		/* this doesn't really work by now
			
		if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
			while (($file = readdir($dir)) !== false) {
				$filePath = $targetDir . DIRECTORY_SEPARATOR . $file;
		
				// Remove temp files if they are older than the max age
				if (preg_match('/\\.tmp$/', $file) && (filemtime($filePath) < time() - $maxFileAge))
					@unlink($filePath);
			}
		
			closedir($dir);
		} else
			die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
		*/
		
		// Look for the content type header
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
			$contentType = $_SERVER["HTTP_CONTENT_TYPE"];
		
		if (isset($_SERVER["CONTENT_TYPE"]))
			$contentType = $_SERVER["CONTENT_TYPE"];
		
		// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
		if (strpos($contentType, "multipart") !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				// Open temp file
				$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen($_FILES['file']['tmp_name'], "rb");
		
					if ($in) {
						while ($buff = fread($in, 4096))
							fwrite($out, $buff);
					} else
						die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
					fclose($in);
					fclose($out);
					@unlink($_FILES['file']['tmp_name']);
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
		} else {
			// Open temp file
			$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");
		
				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
		
				fclose($in);
				fclose($out);
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
		}
		
		// Return JSON-RPC response
		die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');	
	}
	
}