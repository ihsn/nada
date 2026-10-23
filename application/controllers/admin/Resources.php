<?php
class Resources extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
		
		$this->template->set_template('admin');		
       	
		$this->load->model('Survey_resource_model');
		$this->load->model('Catalog_model');
		$this->load->model('managefiles_model');
		$this->load->helper(array ('querystring_helper','url', 'form','file') );		
		$this->load->library('pagination');
		
		//load language file
		$this->lang->load('general');
    	$this->lang->load('catalog_admin');
		$this->lang->load('resource_manager');
		//$this->output->enable_profiler(TRUE);
	}
 
	/**
	*
	* Delete one or more resources
	**/
	function delete($sid,$resource_id)
	{			
		$this->_require_study_edit($sid);
		if (is_numeric($resource_id)) {
			$this->_require_resource_on_study($resource_id, $sid);
		}

		//array of id to be deleted
		$delete_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($resource_id))
		{
			$tmp_arr=explode(",",$resource_id);
		
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
			$delete_arr[]=$resource_id;
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
				$this->Survey_resource_model->delete($item);
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
	 * Download resource
	 * 
	 * 
	 */
	function download($sid=null,$resource_id=null)
	{
		$this->_require_study_edit($sid);
		$this->_require_resource_on_study($resource_id, $sid);
		$resource_filepath=$this->Survey_resource_model->get_resource_download_path($resource_id);

		if (!file_exists($resource_filepath))
		{
			show_error('File not found');
		}

		$this->load->helper('download');
		log_message('info','Downloading file <em>'.$resource_filepath.'</em>');
		force_download2($resource_filepath);
	}
	
	
	/**
	*
	* Edit Resource
	*/
	function edit($id='add')
	{
		$survey_id = $this->_resolve_survey_id_from_uri($id !== 'add' ? $id : null);

		$this->_require_study_edit($survey_id);

		if (!is_numeric($survey_id) || $survey_id < 1)
		{
			$this->session->set_flashdata('error', 'Invalid id was provided.');
			redirect('admin/catalog');			
		}

		if (is_numeric($id) && $id > 0) {
			$this->_require_resource_on_study($id, $survey_id);
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
			redirect("admin/catalog/edit/$survey_id/resources");
		}
	
		$linked_file = '';
		$token = $this->input->get_post('t');
		if ($id === 'add' && is_string($token) && $token !== '') {
			$linked_file = $this->_study_file_from_token($survey_id, $token);
			if ($linked_file === '') {
				show_error('INVALID_PATH', 400);
			}
			$existing = $this->managefiles_model->get_resources_by_filepath($survey_id, $linked_file);
			if (is_array($existing) && isset($existing[0]['resource_id'])) {
				redirect('admin/resources/edit/'.$existing[0]['resource_id'].'/'.$survey_id);
			}
		}

		//redirect on Cancel
		if ( $this->input->post("cancel")!="" )
		{
			$cancel_to = ($linked_file !== '') ? 'files' : 'resources';
			redirect("admin/catalog/edit/$survey_id/".$cancel_to);
		}	
		
		$this->load->library('form_validation');
		
		$data[]=NULL;
		
		//atleast one rule require for validation class to work
		$this->form_validation->set_rules('title', t('title'), 'required|trim');
		
		//validation for resource_idno if provided
		$this->form_validation->set_rules('resource_idno', t('resource_identifier'), 'trim|max_length[100]|callback__validate_resource_idno');
				
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
							
			//map form names with db names; a study-file token wins over a typed path
			if ($linked_file !== '') {
				$options['filename'] = $linked_file;
				$options['survey_id'] = $survey_id;
			} elseif (isset($options['url'])) {
				$options['filename'] = $options['url'];
			}
			
			// Set user information for audit trail
			$user_id = $this->session->userdata('user_id');
			if ($user_id) {
				$options['changed_by'] = $user_id;
				if ($id=='add') {
					$options['created_by'] = $user_id;
				}
			}
						
			if ($id=='add')
			{
				$db_result=$this->Survey_resource_model->insert($options);
				
				//log
				if ($db_result)
				{
					$this->db_logger->write_log('resource-added',$options['title'].'-'.$options['filename'],'resources',$survey_id);
				}	
			}
			else
			{
				//update db
				$db_result=$this->Survey_resource_model->update($id,$options);
				
				//log
				if ($db_result)
				{
					$this->db_logger->write_log('resource-updated',$options['title'].'-'.$options['filename'],'resources',$survey_id);
				}
			}
						
			if (!$db_result)
			{
				//update failed
				$this->form_validation->set_error(t('form_update_fail'));
			}
			else
			{
				//update successful
				$this->session->set_flashdata('message', t('form_update_success') );
				
				//redirect back to the list
				$done_to = ($linked_file !== '') ? 'files' : 'resources';
				redirect("admin/catalog/edit/$survey_id/".$done_to);
			}
		}
		else
		{
			if (is_numeric($id) && $id>0)
			{
				//load values from db
				$data=$this->Survey_resource_model->select_single($id);
				// Normalize dctype to bare code so the dropdown pre-selects correctly
				if (!empty($data['dctype'])) {
					$data['dctype'] = $this->Survey_resource_model->get_resource_type_from_dctype($data['dctype']);
				}
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

		/* DISABLED: Data file dropdown
		//get data files for dropdown
		$this->load->model('Data_file_model');
		$data_files = $this->Data_file_model->get_all_by_survey($survey_id);		
		
		$data['data_files'] = array();
		
		if ($data_files) {
			$data['data_files'][''] = t('__select__'); // empty option
			foreach ($data_files as $file_id => $file) {
				$data['data_files'][$file['id']] = $file['file_name'] . ' (' . $file_id . ')';
			}
		}
		*/
		
		if ($linked_file !== '') {
			$data['filename'] = $linked_file;
			$data['simple_mode'] = true;
			$data['path_token'] = is_string($token) ? $token : '';
			$data['back_link'] = 'admin/catalog/edit/'.$survey_id.'/files';
			$data['back_text'] = t('link_resource_home');
			$data['survey_id'] = $survey_id;
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
		$surveyid = $this->_resolve_survey_id_from_uri();
		if (!is_numeric($surveyid)) {
			return FALSE;
		}
		
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
	 * Validate resource_idno format and uniqueness
	 * 
	 * @param string $str - the resource_idno value
	 * @return bool
	 */
	function _validate_resource_idno($str)
	{
		// If empty, it's valid (will be auto-generated)
		if (trim($str) == "")
		{
			return TRUE;
		}
		
		// Validate format: only alphanumeric, hyphens, and underscores
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $str))
		{
			$this->form_validation->set_message('_validate_resource_idno', 
				t('resource_idno_invalid_format'));
			return FALSE;
		}
		
		// Get survey id and resource id
		$survey_id = $this->_resolve_survey_id_from_uri();
		$resource_id = $this->uri->segment(4); // 'add' or numeric id
		
		// Check uniqueness
		$exclude_id = ($resource_id !== 'add' && is_numeric($resource_id)) ? $resource_id : null;
		
		if ($this->Survey_resource_model->resource_idno_exists($survey_id, $str, $exclude_id))
		{
			$this->form_validation->set_message('_validate_resource_idno', 
				t('resource_idno_already_exists'));
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
		
		$this->_require_study_edit($sid);
		
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
		$this->_require_study_edit($sid);
		
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
						$this->Survey_resource_model->insert($insert_data);
						
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
		$surveyid = $this->_resolve_survey_id_from_uri();
		if (!is_numeric($surveyid)) {
			return FALSE;
		}
		
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
		
		$this->_require_study_edit($surveyid);
	
		$this->load->library('catalog_admin');		
		$fixed_count=$this->catalog_admin->fix_resource_links($surveyid);		
		
		// Build message - fixlinks now also syncs all resource metadata
		$message = sprintf(t('n_resources_fixed'), $fixed_count);
		$message .= ' ' . t('resource_metadata_synced');
		
		$this->session->set_flashdata('message', $message);
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
	 * Relative path for a study file named by the files-screen token.
	 * Empty when the token is missing, the file is not in the study folder, or it is not a file.
	 *
	 * @param int    $survey_id
	 * @param string $token
	 * @return string
	 */
	private function _study_file_from_token($survey_id, $token)
	{
		$rel = $this->_decode_study_file_token($token);
		$rel = ltrim(unix_path($rel), '/');
		if ($rel === '' || strpos($rel, '..') !== false) {
			return '';
		}

		$survey_real = unix_realpath($this->managefiles_model->get_survey_path($survey_id));
		if ($survey_real === false || $survey_real === '') {
			return '';
		}

		$full = unix_realpath(unix_path($survey_real.'/'.$rel));
		if ($full === false || ! is_file($full)) {
			return '';
		}

		$prefix = rtrim(unix_path($survey_real), '/').'/';
		if (strpos(unix_path($full), $prefix) !== 0) {
			return '';
		}

		return $rel;
	}

	/**
	 * @param string $token base64(urlencode(relative/path))
	 * @return string
	 */
	private function _decode_study_file_token($token)
	{
		$token = trim((string) $token);
		$token = str_replace(' ', '+', $token);
		$bin = base64_decode($token, true);
		if ($bin === false || $bin === '') {
			return '';
		}

		$path = urldecode($bin);
		if (! is_string($path) || $path === '') {
			return '';
		}

		$path = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $path);
		return is_string($path) ? $path : '';
	}

	private function _require_study_edit($sid)
	{
		if (! is_numeric($sid) || (int) $sid < 1) {
			show_error('INVALID_STUDY_ID');
		}
		$this->acl_manager->has_access_or_die(
			'study',
			'edit',
			null,
			$this->Catalog_model->get_survey_repositoryid((int) $sid)
		);
	}

	/**
	 * Resolve surveys.id from admin/resources/* URI (add/new/{sid}, edit/{rid}/{sid}, import/{sid}, …).
	 *
	 * @param int|string|null $resource_id when editing, used as fallback lookup
	 * @return int|null
	 */
	private function _resolve_survey_id_from_uri($resource_id = null)
	{
		if ($this->uri->segment(3) === 'add' && $this->uri->segment(4) === 'new') {
			$sid = $this->uri->segment(5);
			if (is_numeric($sid)) {
				return (int) $sid;
			}
		}

		if ($this->uri->segment(3) === 'edit') {
			$sid = $this->uri->segment(5);
			if (is_numeric($sid)) {
				return (int) $sid;
			}
		}

		$sid = $this->uri->segment(4);
		if (is_numeric($sid)) {
			return (int) $sid;
		}

		if ($resource_id !== null && is_numeric($resource_id)) {
			$row = $this->Survey_resource_model->select_single($resource_id);
			if (is_array($row) && isset($row['survey_id']) && is_numeric($row['survey_id'])) {
				return (int) $row['survey_id'];
			}
		}

		return null;
	}

	/**
	 * @param int|string $resource_id
	 * @param int|string $survey_id
	 */
	private function _require_resource_on_study($resource_id, $survey_id)
	{
		if (! is_numeric($resource_id) || ! is_numeric($survey_id)) {
			show_error('INVALID_ID');
		}
		$row = $this->Survey_resource_model->select_single($resource_id);
		if (! is_array($row) || (int) $row['survey_id'] !== (int) $survey_id) {
			show_error('RESOURCE_NOT_FOUND');
		}
	}

}
/* End of file resources.php */
/* Location: ./controllers/admin/resources.php */