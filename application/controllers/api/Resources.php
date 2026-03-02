<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Resources extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model('Dataset_model');
		$this->load->model("Survey_resource_model");
		$this->load->library("Dataset_manager");		
		$this->is_authenticated_or_die();
	}


	/**
	 * 
	 * 
	 * Get all resources by Dataset
	 * 
	 * 
	 */
	function index_get($idno=null,$resource_id=null)
	{	
		try{
						
			if($resource_id){
				return $this->single_get($idno,$resource_id);
			}
			
			$sid=$this->get_sid_from_idno($idno);
			//$this->has_dataset_access('view',$sid);

			$dctype=$this->input->get("dctype");
			$resources=$this->Survey_resource_model->get_resources_by_type($sid,$dctype);
			array_walk($resources, 'unix_date_to_gmt',array('created','changed'));

			if($resources){
				$resources=$this->Survey_resource_model->generate_api_download_link($resources);
			}

			$response=array(
				'status'	=> 'success',
				'total'		=> count($resources),
				'resources'	=> $resources
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}



	/**
	 * 
	 * Get a single resource
	 * 
	 */
	function single_get($idno=null,$resource_id=null)
	{
		try{
			$sid=$this->get_sid_from_idno($idno);
			$this->has_dataset_access('view',$sid);

			if(!is_numeric($resource_id)){
				throw new Exception("MISSING_PARAM: resourceId");
			}
			
			$resource=$this->Survey_resource_model->get_single_resource_by_survey($sid,$resource_id);
			
			if(!$resource){
				throw new Exception("RESOURCE_NOT_FOUND");
			}
						
			$resources=$this->Survey_resource_model->generate_api_download_link(array($resource));
			
			$response=array(
				'status'	=> 'success',
				'resources'	=> $resources
			);

			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}


	
	/**
	 * 
	 * 
	 * create a new external resource
	 * 
	 **/ 
	function index_post($idno=null)
	{
		//multipart/form-data
		$options = $this->input->post(null, true);

		//raw json input
		if (empty($options)) {
			$options = $this->raw_json_input();
		}

		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$options['survey_id'] = $sid;

			//get dctype by code
			if (isset($options['dctype'])) {
				$options['dctype'] = $this->Survey_resource_model->get_dctype_label_by_code($options['dctype']);
			}

			if (isset($options['dcformat'])) {
				$options['dcformat'] = $this->Survey_resource_model->get_dcformat_label_by_code($options['dcformat']);
			}

			//validate resource
			if ($this->Survey_resource_model->validate_resource($options)) {

				$upload_result = null;

				// Support for resumable upload via upload_id
				if (isset($options['upload_id']) && !empty($options['upload_id'])) {
					// Always load via the canonical alias used throughout the application
					$this->load->library('Resumable_upload', null, 'uploader');

					$upload_id = $options['upload_id'];

					// Validate upload and get source file path
					$metadata = $this->uploader->get_upload_metadata($upload_id);
					if (!$metadata) {
						throw new Exception("INVALID_UPLOAD_ID");
					}
					if ($metadata['status'] !== 'completed') {
						throw new Exception("UPLOAD_NOT_COMPLETED");
					}
					$final_file = $this->uploader->get_final_file_path($upload_id);
					if (!$final_file || !file_exists($final_file)) {
						throw new Exception("FILE_NOT_FOUND_FOR_UPLOAD_ID");
					}

					// Copy the uploaded file into the dataset's storage folder so it
					// is co-located with directly-uploaded files
					$this->load->model('Catalog_model');
					$survey_folder = $this->Catalog_model->get_survey_path_full($sid);
					if (!$survey_folder || !file_exists($survey_folder)) {
						throw new Exception("SURVEY_FOLDER_NOT_FOUND");
					}
					$dest_file = unix_path($survey_folder . '/' . basename($final_file));
					if (!copy($final_file, $dest_file)) {
						throw new Exception("FILE_COPY_FAILED");
					}

					$options['filename'] = basename($final_file);
					$upload_result = [
						'file_name' => basename($final_file),
						'file_path' => $dest_file,
						'upload_id' => $upload_id,
						'source'    => 'resumable_upload'
					];
				} elseif (!empty($_FILES)) {
					// Fallback to direct (non-resumable) file upload
					$upload_result = $this->Survey_resource_model->upload_file($sid, 'file', false);
					$options['filename'] = $upload_result['file_name'];
				}

				if (!isset($options['filename'])) {
					$options['filename'] = null;
				}

				$user_id = $this->session->userdata('user_id');
				if ($user_id) {
					$options['changed_by'] = $user_id;
				}

				$resource_id     = null;
				$resource_exists = false;
				$overwrite       = isset($options["overwrite"]) ? $options["overwrite"] : 'yes';

				// First priority: match by resource_idno
				if (isset($options['resource_idno']) && !empty($options['resource_idno'])) {
					$existing_by_idno = $this->Survey_resource_model->get_resource_by_idno($sid, $options['resource_idno']);

					if ($existing_by_idno) {
						if ($overwrite == 'yes' || $overwrite === true) {
							$resource_id = $existing_by_idno['resource_id'];
							$this->Survey_resource_model->update($resource_id, $options);
						} else {
							throw new Exception("Resource with identifier '" . $options['resource_idno'] . "' already exists. To overwrite, set overwrite to 'yes'");
						}
						$resource_exists = true;
					}
				}

				// Second priority: match by filename / title / dctype
				// Skip when filename is null — SQL WHERE filename = NULL never matches,
				// so only attempt this when a real filename is present.
				if (!$resource_exists && !is_null($options['filename'])) {
					$duplicate = $this->Survey_resource_model->check_duplicate($sid, $options['filename'], $options['title'], $options['dctype']);

					if ($duplicate) {
						if ($overwrite == 'yes' || $overwrite === true) {
							$resource_id = $duplicate[0]['resource_id'];
							$this->Survey_resource_model->update($resource_id, $options);
						} else {
							throw new Exception("Resource already exists. To overwrite, set overwrite to 'yes'");
						}
						$resource_exists = true;
					}
				}

				// No existing record found — insert new
				if (!$resource_exists) {
					if ($user_id) {
						$options['created_by'] = $user_id;
					}
					$resource_id = $this->Survey_resource_model->insert($options);
					if (!$resource_id) {
						throw new Exception("RESOURCE_INSERT_FAILED");
					}
				}

				$resource = $this->Survey_resource_model->select_single($resource_id);

				$response = array(
					'status'        => 'success',
					'resource'      => $resource,
					'uploaded_file' => $upload_result
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
		catch(ValidationException $e){
			$error_output=array(
				'message'=>'VALIDATION_ERROR',
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}		
	}


	/**
	 * 
	 * update an existing resource
	 * 
	 **/ 
	function index_put($idno=null,$resource_id=null)
	{
		$options=$this->raw_json_input();

		try{
			$sid=$this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit',$sid);

			if(!is_numeric($resource_id)){
				throw new Exception("MISSING_PARAM: resourceId");
			}

			//get dctype by code
			if(isset($options['dctype'])){
				$options['dctype']=$this->Survey_resource_model->get_dctype_label_by_code($options['dctype']);
			}
			
			$resource=$this->Survey_resource_model->get_single_resource_by_survey($sid,$resource_id);
			
			if(!$resource){
				throw new Exception("RESOURCE_NOT_FOUND");
			}

			$options['survey_id']=$sid;
			$options['resource_id']=$resource_id;
			
			//validate resource
			if ($this->Survey_resource_model->validate_resource($options,$is_new=false)){
				$this->Survey_resource_model->update($resource_id,$options);
				$resource=$this->Survey_resource_model->select_single($resource_id);
				
				$response=array(
					'status'=>'success',
					'resource'=>$resource
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
		catch(ValidationException $e){
			$error_output=array(
				'message'=>'VALIDATION_ERROR',
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}		
	}

	
	/**
	 * 
	 * update using post when PUT is not supported
	 * 
	 **/
	function update_post($idno=null, $resource_id=null)
	{
		return $this->index_put($idno, $resource_id);
	}


	/**
	 * 
	 * 
	 * delete a single resource by resource id
	 * 
	 **/ 
	function index_delete($idno=null,$resource_id=null)
	{
		try{
			$sid=$this->get_sid_from_idno($idno);
			$this->has_dataset_access('delete',$sid);

			if(!is_numeric($resource_id)){
				throw new Exception("MISSING_PARAM: resource_id");
			}

			$isdeleted=$this->Survey_resource_model->delete_single($sid,$resource_id); 

			if(!$isdeleted){
				throw new Exception("RESOURCE_NOT_FOUND");
			}
			
			$response=array(
				'status'=>'success',
				'resource_id'=>$resource_id
			);

			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){		
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);		
		}		
	}

	/**
	 * 
	 * delete using post
	 * 
	 **/ 
	function delete_post($idno=null,$resource_id=null)
	{
		return $this->index_delete($idno,$resource_id);
	}


	/**
	 * 
	 * delete all resources by study
	 * 
	 **/ 
	public function delete_all_delete($idno=null)
	{	
		try{
			$sid=$this->get_sid_from_idno($idno);
			$this->has_dataset_access('delete',$sid);
			$this->Survey_resource_model->delete_all_survey_resources($sid);

			$response=array(
				'status'=>'success'
			);

			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function delete_all_post($idno=null){
		return $this->delete_all_delete($idno);
	}


	/**
	 * 
	 * import rdf file
	 * 
	 **/
	public function import_rdf_post($idno=NULL)
	{
		$this->load->model("Survey_model");	
		
		try {
			$sid=$this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit',$sid);

			$uploaded_path=$this->Survey_resource_model->upload_rdf($tmp_path=null,'file');
			$imported_count=$this->Survey_resource_model->import_rdf($sid,$uploaded_path);
			@unlink($uploaded_path);

			$output=array(
				'status'=>'success',
				'entries_imported'=>$imported_count
			);

			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}

	/**
	 * 
	 * Fix broken links
	 * 
	 */
	function fix_links_put($idno=null)
	{
		$this->load->model("Survey_resource_model");		
		try{
			$sid=$this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit',$sid);
			$links_fixed=$this->Survey_resource_model->fix_resource_links($sid);
			$output=array(
				'links_fixed'=>$links_fixed
			);
			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Download file
	 * 
	 */
	function download_get($dataset_idno=null,$resource_id=null)
	{
		try{
			$sid=$this->get_sid_from_idno($dataset_idno);
			
			//$this->has_dataset_access('edit',$sid);
			$user=$this->api_user();

			if(!$user){
				throw new Exception("USER_NOT_LOGGEDIN");
			}

			if(!$resource_id){
				throw new Exception("PARAM_NOT_SET: resource_id");
			}

			$resource=$this->Survey_resource_model->get_single_resource_by_survey($sid,$resource_id);

			if(!$resource){
				throw new Exception("RESOURCE_NOT_FOUND");
			}
			
			$allow_download=$this->Survey_resource_model->user_has_download_access($user->id,$sid,$resource, $skip_puf=true);

			if($allow_download===false){
				throw new Exception("You don't have permissions to access the file.");
			}

			$this->Survey_resource_model->download($user,$sid,$resource_id);
			die();
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}



	/**
	 * 
	 * 
	 * List external resources links by study IDNO
	 * 
	 * 
	 */
	function download_links_post()
	{
		//multipart/form-data
		$options=$this->input->post(null, true);

		//raw json input
		if (empty($options)){
			$options=$this->raw_json_input();
		}
				
		try{
			if(!isset($options['idno_list'])){
				throw new Exception("Param required: idno_list");
			}

			$links_generator=$this->Survey_resource_model->get_download_links($options['idno_list']);

			$links=array();
			foreach($links_generator as $link){
				$links[]=$link;
			}			

			if(isset($options['format']) && $options['format']=='csv')
			{
				header('Content-Disposition: attachment; filename=resources-download-links.csv');
				header("Content-Type: text/plain");
				$delimiter = ',';
				$enclosure = '"';				

				$fp = fopen("php://output", 'w');
				fputcsv($fp,array_keys($links[0]),$delimiter,$enclosure);

				foreach ($links as $fields) {
					fputcsv($fp, $fields,$delimiter,$enclosure);
				}

				fclose($fp);
				return;
			}
			
			$response=array(
				'status'=>'success',
				'links'=>$links
			);

			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}	
	}	


	//override authentication to support both session authentication + api keys
	function _auth_override_check()
	{
		if ($this->session->userdata('user_id')){
			return true;
		}
		parent::_auth_override_check();
	}

}
