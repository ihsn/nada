<?php
class Resources extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
		
		$this->template->set_template('admin');		
       	
		$this->load->model('Resource_model');
		$this->load->model('Catalog_model');
		$this->load->model('managefiles_model');
		$this->load->helper(array ('querystring_helper','url', 'form','file') );		
		$this->load->library('pagination');
		
		//load language file
		$this->lang->load('general');
    	$this->lang->load('catalog_admin');
		$this->lang->load('resource_manager');
		$this->lang->load('plupload');

		//$this->output->enable_profiler(TRUE);
	}
 
	function index(){	

		//required
		$surveyid=$this->uri->segment(3);
		
		if (!is_numeric($surveyid) )
		{
			show_error('Invalid or missing parameters');
		}
		
		//test user study permissions
		$this->acl->user_has_study_access($surveyid);
		
		//get survey folder path
		$this->survey_folder=$this->Catalog_model->get_survey_path_full($surveyid);

		//set parent survey
		$this->Resource_model->surveyid=$surveyid;
				
		//get records		
		$result['rows']=$this->_search();
		
		//load the contents of the page into a variable
		$content=$this->load->view('resources/index', $result,true);
	
		//page title
		$this->template->write('title', t('resource_manager'),true);

		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	
	/**
	* returns the paginated resources
	* 
	* supports: sorting, searching, pagination
	*/
	function _search()
	{		
		//records to show per page
		$per_page = $this->input->get("ps");
		
		if($per_page===FALSE || !is_numeric($per_page))
		{
			$per_page=15;
		}
				
		//current page
		$curr_page=$this->input->get('per_page');//$this->uri->segment(4);

		//records
		$rows=$this->Resource_model->search($per_page, $curr_page);

		//total records in the db
		$total = $this->Resource_model->search_count;

		if ($curr_page>$total)
		{
			$curr_page=$total-$per_page;
			
			//search again
			$rows=$this->Resource_model->search($per_page, $curr_page);
		}
		
		//set pagination options
		$base_url = site_url("admin/catalog/{$this->Resource_model->surveyid}/resources");
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
		$this->pagination->initialize($config); 
		return $rows;		
	}

	/**
	* show single resource on the page
	* 
	*/
	function view($resourceid)
	{
		//get db row by id
		$row=$this->Resource_model->select_single($resourceid);
		
		$data['row']=$row;
		$data['textarea_fields']=array('abstract','toc');
		//load the contents of the page into a variable
		$content=$this->load->view('resources/single_record', $data,true);
		
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();		
	}
	
	/**
	*
	* Delete one or more resources
	**/
	function delete($id)
	{			
		//array of id to be deleted
		$delete_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($id))
		{
			$tmp_arr=explode(",",$id);
		
			foreach($tmp_arr as $key=>$value)
			{
				if (is_numeric($value))
				{
					$delete_arr[]=$value;
				}
			}
			
			if (count($delete_arr)==0)
			{
				//for ajax return JSON output
				if ($ajax!='')
				{
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
				}
				
				$this->session->set_flashdata('error', 'Invalid id was provided.');
				redirect('admin/catalog');
			}	
		}		
		else
		{
			$delete_arr[]=$id;
		}
		
		if ($this->input->post('cancel')!='')
		{
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/catalog');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->Resource_model->delete($item);
			}

			//for ajax calls, return output as JSON						
			if ($ajax!='')
			{
				echo json_encode(array('success'=>"true") );
				exit;
			}
						
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/catalog');
			}	
		}
		else
		{
			//ask for confirmation
			$content=$this->load->view('resources/delete', NULL,true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}
	
	
	/**
	*
	* Edit Resource
	*/
	function edit($id='add')
	{
		//check survey id 
		$survey_id=$this->uri->segment(5);
		
		//test user study permissiosn
		$this->acl->user_has_study_access($survey_id);
		
		if (!is_numeric($survey_id) && $survey_id<1)
		{
			$this->session->set_flashdata('error', 'Invalid id was provided.');
			redirect('admin/catalog');			
		}
		
		//js auto complete file list
		$files_array=$this->get_files_array($survey_id);
		$this->js_files='';
		
		if (is_array($files_array))
		{
			$this->js_files=implode(' ', $files_array);
		}
		
		//check id
		if (!is_numeric($id) && $id!='add')
		{
			$this->session->set_flashdata('error', 'Invalid id was provided.');
			redirect("admin/catalog/$survey_id/resources");
		}
	
		//redirect on Cancel
		if ( $this->input->post("cancel")!="" )
		{
			redirect("admin/catalog/edit/$survey_id/resources");
		}	
		
		$this->load->library('form_validation');
		
		$data[]=NULL;
		
		//atleast one rule require for validation class to work
		$this->form_validation->set_rules('title', t('title'), 'required|trim');		
				
		if ($this->form_validation->run() == TRUE)
		{
			$options=array();
			$post_arr=$_POST;
			
			//read post values to pass to db
			foreach($post_arr as $key=>$value)
			{
				$options[$key]=$this->input->post($key);
			}
			
			if ($id=='add')
			{
				//set survey_id
				$options['survey_id']=$survey_id;				
			}
			else
			{
				//set resource id			
				$options['resource_id']=$id;
			}
							
			//map form names with db names
			$options['filename']=$options['url'];
						
			if ($id=='add')
			{
				$db_result=$this->Resource_model->insert($options);
				
				//log
				if ($db_result)
				{
					$this->db_logger->write_log('resource-added',$options['title'].'-'.$options['filename'],'resources',$survey_id);
				}	
			}
			else
			{
				//update db
				$db_result=$this->Resource_model->update($id,$options);
				
				//log
				if ($db_result)
				{
					$this->db_logger->write_log('resource-updated',$options['title'].'-'.$options['filename'],'resources',$survey_id);
				}
			}
						
			if ($db_result===TRUE)
			{
				//update successful
				$this->session->set_flashdata('message', t('form_update_success') );
				
				//redirect back to the list
				redirect("admin/catalog/edit/$survey_id/resources");
			}
			else
			{
				//update failed
				$this->form_validation->set_error(t('form_update_fail'));				
			}
		}
		else
		{
			if (is_numeric($id) && $id>0)
			{
				//load values from db
				$data=$this->Resource_model->select_single($id);
			}	
		}
				
		if (is_numeric($id))
		{
			$data['form_title']=t('edit_external_resource');
		}
		else
		{
			$data['form_title']=t('add_external_resource');
		}
		
		//load form
		$content=$this->load->view('resources/edit', $data,true);
		
		$this->template->write('content', $content,true);
	  	$this->template->render();		
	}



	/**
	* validate for file or url
	*
	*/
	function _valid_file_url($str)
	{
		
		if (trim($str)=="")
		{
			return TRUE;
		}
		
		//get survey id
	 	$surveyid=$this->uri->segment(3);		
		
		//get relative survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path($surveyid);
		
		//catalog folder path
		$catalog_root=$this->config->item("catalog_root");
		
		//complete survey folder path
		$survey_folder=$catalog_root.'/'.$survey_folder;
		
		//file path
		$file_path=$survey_folder.'/'.$str;
	 
	 	//validate as file
	 	if (file_exists($file_path) )
		{
			return TRUE;
		}
		
		//validate as a URL
		if ($this->form_validation->valid_url($str)===FALSE)
		{
			$this->form_validation->set_message('_valid_file_url', lang('valid_url'));
			return FALSE;
		}
		
		return TRUE;				
	}
	
	/**
	 * upload files to survey folder
	 * used for uploading survey reports,technical docs
	 * 
	 * @return array
	 */
	function _upload_file($key,$destination)
	{
		if ($_FILES[$key]['size']==0)
		{
			return false;
		}
		
		$config['upload_path'] = $destination;
		$config['disallowed_types'] = 'exe|php|js|asp|aspx';
		$config['overwrite'] = true;
		
		$this->load->library('upload', $config);
	
		if ( ! $this->upload->do_upload($key))
		{
			//failed
			throw new Exception( $this->upload->display_errors() );
		}	
		else
		{
			$data = $this->upload->data();
			return $data;
		}
	}
	

	function add()
	{
		$this->edit("add");
	}
	
	function import($sid)
	{
	
		if (!is_numeric($sid))
		{
			show_error("invalid_id");
		}
		
		//test user study permissiosn
		$this->acl->user_has_study_access($sid);
		
		$this->load->library('form_validation');
		
		if ($this->input->post('submit'))
		{
			$this->do_import($sid);
			//echo 'importing';
		}
		else
		{
			$data['form_title']=t('import_external_resources');
			//import form
			$content=$this->load->view('resources/import', $data,true);			
			$this->template->write('content', $content,true);
			$this->template->render();	
		}
	}
	
	function do_import($sid)
	{
		//test user study permissiosn
		$this->acl->user_has_study_access($sid);
		
		//catalog folder path
		$catalog_root=$this->config->item("catalog_root");
		
		//if not fixed path, use a relative path
		if (!file_exists($catalog_root) )
		{
			$catalog_root=FCPATH.$catalog_root;
		}		
		
		//create .htaccess if not already exists
		@file_put_contents($catalog_root.'/.htaccess','deny from all');
		@chmod($catalog_root.'/.htaccess',0444);
		
		$temp_upload_folder=$catalog_root.'/tmp';
		
		@mkdir($temp_upload_folder);
		
		if (!file_exists($temp_upload_folder))
		{
			show_error('DATAFILES-TEMP-FOLDER-NOT-SET');
		}

		//upload class configurations
		$config['upload_path'] 	 = $temp_upload_folder;
		$config['overwrite'] 	 = FALSE;
		$config['encrypt_name']	 = TRUE; 
		$config['allowed_types'] = 'rdf';
		
		//load upload library
		$this->load->library('upload', $config);

		//process uploaded file
		if ( ! $this->upload->do_upload())
		{
			//get errors while uploading
			$error = $this->upload->display_errors();
			
			//set error
			$this->session->set_flashdata('error', $error);
			
			//redirect back to the upload page
			redirect('admin/resources/import/'.$sid,'refresh');
		}	
		else //successful upload
		{			
			//get uploaded file information
			$data = $this->upload->data();
																			
			//read rdf file contents
			$rdf_contents=file_get_contents($data['full_path']);
			
			//load RDF parser class
			$this->load->library('RDF_Parser');
			
			//parse RDF to array
			$rdf_array=$this->rdf_parser->parse($rdf_contents);
			
			if ($rdf_array===FALSE || $rdf_array==NULL)
			{
				$this->session->set_flashdata('error', t('error_import_failed') );
			}
			else
			{
				$rdf_fields=$this->rdf_parser->fields;
				
				//success
				foreach($rdf_array as $rdf_rec)
				{
					$insert_data['survey_id']=$sid;
					
					foreach($rdf_fields as $key=>$value)
					{
						if ( isset($rdf_rec[$rdf_fields[$key]]))
						{
							$insert_data[$key]=trim($rdf_rec[$rdf_fields[$key]]);
						}	
					}										
					
					//check if it is not a URL
					if (!is_url($insert_data['filename']))
					{
						//fix html entities e.g &amp; &quot;
						$insert_data['filename']=htmlspecialchars_decode($insert_data['filename'],ENT_QUOTES);
						
						//clean file paths
						$insert_data['filename']=unix_path($insert_data['filename']);

						//remove slash before the file path otherwise can't link the path to the file
						if (substr($insert_data['filename'],0,1)=='/')
						{
							$insert_data['filename']=substr($insert_data['filename'],1,255);
						}												
					}
					
					//check if the resource file already exists
					$resource_exists=FALSE;//$this->Resource_model->get_survey_resources_by_filepath($insert_data['survey_id'],$insert_data['filename']);
					
					if (!$resource_exists)
					{										
						//insert into db
						$this->Resource_model->insert($insert_data);
						
						//log
						$this->db_logger->write_log('resource-imported',$insert_data['title'].'-'.$insert_data['filename'],'resources',$insert_data['survey_id']);
											
						//create resources folder structure
						if ($this->input->post('folder_structure')=='yes')
						{						
							$this->_create_folder_structure($insert_data['filename']);
						}
					}
					else
					{
						$this->errors[]=t('resource_already_exists').'<b>'. $insert_data['filename'].'</b>';
					}
				}

				if (isset($this->errors) )
				{
						$this->session->set_flashdata('error', implode('<BR>',$this->errors));
				}
				
				$this->session->set_flashdata('message', sprintf(t('n_resources_imported'),count($rdf_array)) );	
			}
			
			//fix resources file paths
			$this->load->library('catalog_admin');
			$this->catalog_admin->fix_resource_links($sid);
			
			//redirect
			redirect('admin/catalog/edit/'.$sid.'/resources','refresh');
		}
	}	

	/**
	* Create folder structure for imported external resources
	*
	**/
	function _create_folder_structure($str)
	{				
		if ($str=='' || strpos($str,'http:') || strpos($str,'https:') || strpos($str,'ftp:') ||  strpos($str,'../') ||  strpos($str,'./') )
		{
			return FALSE;
		}

		//change file slash
		$str=unix_path($str);
		
		//strip filename from the path
		$path_arr=explode('/', $str);

		//assuming the last part is always going to be the filename
		unset($path_arr[count($path_arr)-1]);

		//folder path without filename
		$folder_structure=trim(implode('/',$path_arr));

		if ($folder_structure=='')
		{
			return FALSE;
		}
				
		//get survey id
	 	$surveyid=$this->uri->segment(3);		
		
		//get relative survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path($surveyid);
		
		//catalog folder path
		$catalog_root=$this->config->item("catalog_root");
		
		//complete survey folder path
		$survey_folder=$catalog_root.'/'.$survey_folder;
		
		if (!file_exists($survey_folder.'/'.$folder_structure))
		{
			log_message('debug', 'Creating folder for external resources - '.$survey_folder.'/'.$folder_structure);
			if (!@mkdir($survey_folder.'/'.$folder_structure, 0777, true)) 
			{
				log_message('debug', 'Failed creating folder for external resources - '.$survey_folder.'/'.$folder_structure);
			}
		}
	}


	/**
	*
	* Fix broken file links. It updates the resource paths in the database if the 
	* files exists in the survey folder
	*
	*/
	function __fixlinks($surveyid=NULL)
	{
		if ($this->input->post("submit"))
		{
			$content=$this->_fix_links($surveyid);					
		}
		else
		{
			$content=$this->load->view('managefiles/fixlinks',array('links'=>array()),TRUE );
		}
		
		$this->template->write('content', $content,true);
		$this->template->render();				
	}
	
	function fixlinks($surveyid)
	{
		if (!is_numeric($surveyid))
		{
			show_error("invalid_id");
		}
		
		//test user study permissiosn
		$this->acl->user_has_study_access($surveyid);
	
		$this->load->library('catalog_admin');		
		$fixed_count=$this->catalog_admin->fix_resource_links($surveyid);		
		
		$this->session->set_flashdata('message', sprintf (t('n_resources_fixed'),$fixed_count));
		redirect('admin/catalog/edit/'.$surveyid.'/resources',"refresh");
	}
	
	/**
	*
	* Returns an array of all files in the survey folder
	*
	**/
	function get_files_array($surveyid=NULL)
	{
		if (!is_numeric($surveyid))
		{
			return NULL;
		}
		
		//get survey folder path
		$folderpath=$this->managefiles_model->get_survey_path($surveyid);
		
		//name of the file to lock for deletion
		$this->ddi_file_name=basename($this->Catalog_model->get_survey_ddi_path($surveyid));
			
		//get all survey files
		$data=$this->managefiles_model->get_files_recursive($folderpath,$folderpath);
		
		$files=array();
		
		if (isset($data['files']))
		{
			foreach($data['files'] as $file)
			{
				$files[]=$file['relative'].'/'.$file['name'];
			}
		}		
		return $files;
	}
	
	/**
	*
	* Upload external resource files for survey
	**/
	function upload($sid=NULL)
	{
		if (!is_numeric($sid))
		{
			show_error("INVALID");
		}
		
		$this->acl->user_has_study_access($sid);
		
		if($_FILES)
		{
			$this->_process_upload($sid);
		}		
		
		$data['breadcrumbs']=array(
			'admin/catalog'					=> 	'Catalog',
			'admin/catalog/edit/'.$sid		=>	'Edit Survey',
			''								=>	'Upload Resources'
		);
		
		$data['survey']=$this->Catalog_model->select_single($sid);
		$content=$this->load->view('resources/plupload',$data,TRUE);
		
		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);		
		$this->template->write('content', $content,true);
		$this->template->render();
	}
	
	
	private function _process_upload($sid)
	{
		$this->load->helper('text');
		$files=$_FILES;
		
		if (!$files)
		{
			return;
		}
		
		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);
		
		foreach ($files["file"]["error"] as $key => $error) 
		{
		    if ($error == UPLOAD_ERR_OK) 
			{
		        $tmp_name = $_FILES["file"]["tmp_name"][$key];
        		$name = $_FILES["file"]["name"][$key];
								
				$name=convert_accented_characters($name);
		
				//log
				$this->db_logger->write_log('resource-upload',$name,'resources',$sid);
		
				//check if allowed file type
				$file_info=pathinfo($name);
				$allowed_types=explode(",",$this->config->item("allowed_resource_types"));
				
				if (!in_array($file_info['extension'],$allowed_types))
				{	
					$this->db_logger->write_log('resource-upload-failed','blocked - '.$name,'resources',$sid);
				}
				else
				{				
		        	move_uploaded_file($tmp_name, "$survey_folder/$name");
				}	
    		}
		}
		
		redirect('admin/catalog/edit/'.$sid);
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
	function pl_uploads($surveyid,$overwrite=1)
	{
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

		if (!$fileName)
		{
			return;
		}
		
		$this->load->helper('text');
		$fileName=convert_accented_characters($fileName);

		//log
		$this->db_logger->write_log('resource-upload',$fileName,'resources',$surveyid);

		//check if allowed file type
		$file_info=pathinfo($fileName);
		$allowed_types=explode(",",$this->config->item("allowed_resource_types"));
		
		if (!in_array(strtolower($file_info['extension']),$allowed_types))
		{	
			$this->db_logger->write_log('resource-upload-failed','blocked - '.$fileName,'resources',$surveyid);
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "invalid-file-type"}, "type" : "{$file_info["extension"]}"}');				
		}
				
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
			log_message('info', 'create folder: '.$targetDir);
		}
		
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
/* End of file resources.php */
/* Location: ./controllers/admin/resources.php */