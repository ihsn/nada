<?php

require(APPPATH.'/libraries/REST_Controller.php');

class Study extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Catalog_model'); 	
		$this->load->library('DDI_Browser','','DDI_Browser');
		$this->load->helper("date");
		$this->load->model('Survey_model');	//tODO remove
		$this->load->model('Survey_type_model');	
		$this->load->model('Data_file_model');
		$this->load->model('Variable_model');	
		$this->load->model('Dataset_model');		
	}

	private function get_api_user_id()
	{
		if(isset($this->_apiuser) && isset($this->_apiuser->user_id)){
			return $this->_apiuser->user_id;
		}

		return false;
	}

	//return raw json input
	private function raw_json_input()
	{
		$data=$this->input->raw_input_stream;				
		//$data = file_get_contents("php://input");

		if(!$data || trim($data)==""){
			return null;
		}
		
		$json=json_decode($data,true);

		if (!$json){
			throw new Exception("INVALID_JSON_INPUT");
		}

		return $json;
	}
	


	/**
	 * 
	 * 
	 * get by ID
	 * 
	 */
	function index_get($sid=null)
	{
		try{

			//find dataset type
			$type=$this->Dataset_model->get_type($sid);

			if(!$type){
				throw new Exception("NOT_FOUND");
			}

			$dataset=$this->Dataset_model->get_row_detailed($sid);

			$response=array(
				'status'=>'success',
				'dataset'=>$dataset
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
	 * 
	 * Create new study
	 * @type - survey, timesereis, geospatial
	 * 
	 */
	function create_post($type=null)
	{
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			$options['created_by']=$user_id;
			$options['changed_by']=$user_id;
			$options['created']=date("U");
			$options['changed']=date("U");
			
			//validate & create study
			$dataset_id=$this->Dataset_model->create_dataset($type,$options);

			if(!$dataset_id){
				throw new Exception("FAILED_TO_CREATE_DATASET");
			}

			$dataset=$this->Dataset_model->get_row_detailed($dataset_id);

			$response=array(
				'status'=>'success',
				'dataset'=>$dataset
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
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
	 * list study data files
	 * 
	 */
	function datafiles_get($sid=null)
	{
		try{			
			if (!$sid){
				throw new Exception("MISSING_PARAM: sid"); 
			}

			$user_id=$this->get_api_user_id();        
			$survey=$this->Survey_model->get_row($sid);

			if(!$survey){
				throw new exception("STUDY_NOT_FOUND");
			}

			$survey_datafiles=$this->Data_file_model->get_all_by_survey($sid);
			
			//format dates
			//array_walk($project, 'unix_date_to_gmt_row',array('created','changed','submitted_date','administer_date'));

			$response=array(
				'datafiles'=>$survey_datafiles
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
	 * 
	 * Create new data file
	 * 
	 */
	function create_datafile_post($sid)
	{
		try{
			//post data
			//$options=$this->input->post();

			//raw data
			$options=$this->raw_json_input();

			$user_id=$this->get_api_user_id();
			$options['created_by']=$user_id;
			$options['changed_by']=$user_id;

			//validate 
			if ($this->Data_file_model->validate_data_file($options)){
				
				$file_id=$this->Data_file_model->insert($sid,$options);
				$file=$this->Data_file_model->select_single($file_id);

				$response=array(
					'status'=>'success',
					'datafile'=>$file
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
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
	 * list study data files
	 * 
	 */
	function variables_get($sid=null,$file_id=null)
	{
		try{			
			if (!$sid){
				throw new Exception("MISSING_PARAM: sid"); 
			}

			$user_id=$this->get_api_user_id();        
			$survey=$this->Survey_model->get_row($sid);

			if(!$survey){
				throw new exception("STUDY_NOT_FOUND");
			}

			$survey_variables=$this->Variable_model->list_by_study($sid);
			
			//format dates
			//array_walk($project, 'unix_date_to_gmt_row',array('created','changed','submitted_date','administer_date'));

			$response=array(
				'variables'=>$survey_variables
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

	function variable_get($sid=null,$var_id=null)
	{
		try{			
			if (!$sid){
				throw new Exception("MISSING_PARAM: sid"); 
			}

			$user_id=$this->get_api_user_id();        
			$survey=$this->Survey_model->get_row($sid);

			if(!$survey){
				throw new exception("STUDY_NOT_FOUND");
			}

			$variable=$this->Variable_model->get_var_by_vid($sid,$var_id);
			
			//format dates
			//array_walk($project, 'unix_date_to_gmt_row',array('created','changed','submitted_date','administer_date'));

			$response=array(
				'variable'=>$variable
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
	 * 
	 * Create new variable
	 * @sid - study id
	 * @file_id - user defined file id e.g. F1
	 * 
	 */
	function create_variable_post($sid=null,$file_id=null)
	{
		try{
			//raw data
			$options=$this->raw_json_input();

			$user_id=$this->get_api_user_id();

			//validate study id
			if(!$this->Dataset_model->get_idno($sid)){
				throw new exception("DATASET_NOT_FOUND");
			}

			//get file id
			$fid=$this->Data_file_model->get_fid_by_fileid($sid,$file_id);

			if(!$fid){
				throw new exception("FILE_NOT_FOUND: ".$file_id);
			}

			//variables should be passed as array
			
			//validate all variables
			foreach($options as $key=>$variable){
				$this->Variable_model->validate_variable($variable);
			}

			$result=array();
			foreach($options as $variable)
			{
				$variable['fid']=$file_id;
				//all fields are stored as metadata
				$variable['metadata']=$variable;
				$variable_id=$this->Variable_model->insert($sid,$variable);
				$variable=$this->Variable_model->select_single($variable_id);
				$result[$variable['vid']]=$variable;
			}

			//update survey varcount
			$this->Survey_model->update_varcount($sid);

			$response=array(
				'status'=>'success',
				'variables'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	function create_series_post($sid=null,$file_id=null)
	{
		return $this->create_variable_post($sid,$file_id);
	}

	//remove all variables from a data file
	function datafile_empty_vars_delete($sid=null, $file_id){
		$this->Dataset_model->remove_datafile_variables($sid,$file_id);
	}



	/** 
	 * 
	 * 
	 * Import a ddi 2 codebook xml file
	 * 
	 * @overwrite - yes|no - overwrite existing project
	 * @repositoryid - repository ID
	 * @file - uploaded file
	 * 
	 * 
	 **/ 
	function import_ddi_post()
	{
		$this->import_post('survey');
	}


	/** 
	 * 
	 * 
	 * Import a file
	 * 
	 * import a metadata file e.g. ddi or geospatial file
	 * 
	 * @overwrite - yes|no - overwrite existing project
	 * @repositoryid - repository ID
	 * @type - project type 
	 * @file - uploaded file
	 * 
	 * 
	 **/ 
	function import_post($type)
	{
		$this->load->library('ion_auth');
		$this->load->library('acl');
		$this->load->model("Dataset_model");		

		$overwrite=$this->input->post("overwrite")=='yes' ? TRUE : FALSE;
		$repositoryid=$this->input->post("repositoryid");
		//$survey_type='geospatial';
		$dataset_type=$type;

		if (!$repositoryid){
			$repositoryid='central';
		}


		if(!$dataset_type){
			throw new Exception("DATASET_TYPE_NOT_SET");
		}

		try{
			//user has permissions on the repo or die
			$this->acl->user_has_repository_access($repositoryid,$this->get_api_user_id());
					
			//process form

			$temp_upload_folder=get_catalog_root().'/tmp';
			
			if (!file_exists($temp_upload_folder)){
				@mkdir($temp_upload_folder);
			}
			
			if (!file_exists($temp_upload_folder)){
				show_error('DATAFILES-TEMP-FOLDER-NOT-SET');
			}

			//upload class configurations for DDI
			$config['upload_path'] 	 = $temp_upload_folder;
			$config['overwrite'] 	 = FALSE;
			$config['encrypt_name']	 = TRUE;
			$config['allowed_types'] = 'xml';

			$this->load->library('upload', $config);

			//name of the field for file upload
			$file_field_name='file';
			
			//process uploaded ddi file
			$ddi_upload_result=$this->upload->do_upload($file_field_name);

			$uploaded_ddi_path=NULL;

			//ddi upload failed
			if (!$ddi_upload_result){
				$error = $this->upload->display_errors();
				$this->db_logger->write_log('ddi-upload',$error,'catalog');
				throw new Exception($error);
			}
			else //successful upload
			{
				//get uploaded file information
				$uploaded_ddi_path = $this->upload->data();
				$uploaded_ddi_path=$uploaded_ddi_path['full_path'];
				$this->db_logger->write_log('ddi-upload','success','catalog');
			}		

		
			$this->load->library('DDI2_import');
			$params=array(
				'file_type'=>$dataset_type, 
				'file_path'=>$uploaded_ddi_path,
				'repositoryid'=>$repositoryid,
				'published'=>1,
				'user_id'=>$this->get_api_user_id(),
				'formid'=>6,
				'overwrite'=>$overwrite
			);			
			
			$result=$this->ddi2_import->import($params);
			//$result['sid']=$this->metadata_import->get_sid();
			//$result['message']="import successful!";
			$response=array(
				'status'=>'success',
				'survey'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}

		//upload class configurations for RDF
		$config['upload_path'] = $temp_upload_folder;
		$config['overwrite'] = FALSE;
		$config['encrypt_name']=TRUE;
		$config['allowed_types'] = 'rdf';

		$this->upload->initialize($config);

		//process uploaded rdf file
		$rdf_upload_result=$this->upload->do_upload('rdf');

		$uploaded_rdf_path='';

		if ($rdf_upload_result)
		{
			$uploaded_rdf_path = $this->upload->data();
			$uploaded_rdf_path=$uploaded_rdf_path['full_path'];
		}

		if (isset($result['sid']) && $uploaded_rdf_path!="")
		{
			//import rdf
			$this->Survey_model->import_rdf($result['sid'],$uploaded_rdf_path);

			//delete rdf
			@unlink($uploaded_rdf_path);
		}
	}



	//import rdf file
	public function import_rdf_post($sid=NULL)
	{
		$this->load->model("Survey_model");	

		if(!$sid){
			throw Exception("SID_NOT_SET");
		}
		
		$temp_upload_folder=get_catalog_root().'/tmp';
		
		if (!file_exists($temp_upload_folder)){
			@mkdir($temp_upload_folder);
		}
		
		if (!file_exists($temp_upload_folder)){
			show_error('DATAFILES-TEMP-FOLDER-NOT-SET');
		}
		
		//upload class configurations for RDF
		$config['upload_path'] = $temp_upload_folder;
		$config['overwrite'] = FALSE;
		$config['encrypt_name']=TRUE;
		$config['allowed_types'] = 'rdf';

		$this->load->library('upload', $config);
		//$this->upload->initialize($config);

		//process uploaded rdf file
		$rdf_upload_result=$this->upload->do_upload('rdf');

		$uploaded_rdf_path='';

		if ($rdf_upload_result){
			$uploaded_rdf_path = $this->upload->data();
			$uploaded_rdf_path=$uploaded_rdf_path['full_path'];
		}

		if ($uploaded_rdf_path!=""){
			//import rdf
			$result=$this->Survey_model->import_rdf($sid,$uploaded_rdf_path);

			//delete rdf
			@unlink($uploaded_rdf_path);
		}

		var_dump($result);
	}


	//get study metadata
	function metadata_get($sid)
	{
		$this->load->model("Survey_model");
		$metadata=$this->Survey_model->get_metadata($sid);
		$this->set_response($metadata, REST_Controller::HTTP_OK);
	}



	//list studies by current user
	public function list_studies_by_user_get($limit=20)
	{
		
		$this->load->model('Survey_model');
		$userid=$this->get_api_user_id();
		$studies=$this->Survey_model->get_studies_by_user($userid,$limit=20);

		//convert date fields to GMT
		array_walk($studies, 'unix_date_to_gmt',array('created','changed'));
		$this->set_response($studies, REST_Controller::HTTP_OK);
	}

	//list recent studies
	public function list_recent_studies_get($limit=20)
	{
		$studies=$this->Survey_model->get_recent_studies($limit=20);

		//convert dates to GMT
		array_walk($studies, 'unix_date_to_gmt',array('created','changed'));
		$this->set_response($studies, REST_Controller::HTTP_OK);
	}


	/**
	 * 
	 *  Set study status
	 * 
	 * @sid - study id
	 * @publish_status - 1=publish, 0=unpublish
	 * 
	 */
	public function set_publish_status_put($sid=null,$publish_status=null)
	{		
		try{
			if(!is_numeric($sid) || !is_numeric($publish_status)){
				throw new Exception("MISSING_PARAMS");
			}
			$this->Survey_model->set_publish_status($sid,$publish_status);
			$this->set_response('UPDATED', REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}				
	}

	public function delete_delete($sid){
		$this->Survey_model->delete($sid);
		$this->set_response('UPDATED', REST_Controller::HTTP_OK);
	}


	/**
	 * 
	 * 
	 * Set the owner collection for the study or Transfer ownership
	 * 
	 * @sid - study id
	 * @repositoryid - collection numeric id
	 * 
	 */
	public function transfer_ownership_post(){
		
		$sid=$this->input->post("sid");
		$repositoryid=$this->input->post("repositoryid");

		try{
			if (!$sid || !$repositoryid){
				throw new Exception("PARAM_MISSING");
			}

			//user has permissions on the repo			
			//$this->acl->user_has_repository_access($repositoryid);
					
			//validate repository
			if ($repositoryid=='central'){
				$exists=true;
			}
			else{
				$exists=$this->Catalog_model->repository_exists($repositoryid);
			}

			if (!$exists){
				throw new Exception(t('COLLECTION_NOT_FOUND'));
			}

			//transfer ownership
			$this->Catalog_model->transfer_ownership($repositoryid,$sid);
			$this->set_response(t('msg_study_ownership_has_changed'), REST_Controller::HTTP_OK);		
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}	
	}

	//list data access types
	function list_data_access_types_get(){
		$this->load->model("Form_model");
		$types=$this->Form_model->data_access_types_list();
		$this->set_response($types, REST_Controller::HTTP_OK);		
	}

	/**
	 * 
	 * 
	 * set data access options
	 * 
	 * 
	 * @sid
	 * @da_type	- numeric data access type id
	 * @da_link	- only required for remote data access
	 * 
	 * Note: use list_data_access_types to get a list of available data access types
	 * 
	 **/ 
	function set_data_access_type_post()
	{
		$sid=$this->input->post("sid");
		$da_type=$this->input->post("da_type");
		$da_link=$this->input->post("da_link");		

		try{

			if (!$sid || !is_numeric($sid)){
				throw new Exception("INVALID_VALUE: SID");
			}

			if (!$da_type || !is_numeric($da_type)){
				throw new Exception("INVALID_VALUE: da_type");
			}

			if ($da_type==5 &&  !$da_link){
				throw new Exception("VALUE_MISSING: da_link");
			}

			$result=$this->Survey_model->set_data_access_type($sid,$da_type,$da_link);			
			$this->set_response($result, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
		
	}

	

	//get study external resources list
	function external_resources_list_get($sid=null)
	{	
		try{
			if(!is_numeric($sid)){
				throw new Exception("MISSING_PARAM: sid");
			}

			$this->load->model("Resource_model");
			$resources=$this->Resource_model->get_resources_by_survey($sid);
			$this->set_response($resources, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	//returns the schema definition for external resources
	function external_resource_schema_get()
	{

	}

	
	//create a new external resource
	function external_resource_create_post()
	{
		$this->load->model("Survey_resource_model");
		$options=$this->input->post();
		
		try{
			//validate resource
			if ($this->Survey_resource_model->validate_resource($options)){
				if (isset($options['url'])){
					$options['filename']=$options['url'];
				}
				$resource_id=$this->Survey_resource_model->insert($options);
				$resource=$this->Survey_resource_model->select_single($resource_id);
				$this->set_response($resource, REST_Controller::HTTP_OK);
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


	//create a new external resource
	function external_resource_update_post()
	{
		$this->load->model("Survey_resource_model");
		$options=$this->input->post();

		try{
			//validate resource
			if ($this->Survey_resource_model->validate_resource($options,$is_new=false)){
				if (isset($options['url'])){
					$options['filename']=$options['url'];
				}
				$resource_id=$options['resource_id'];
				$this->Survey_resource_model->update($resource_id,$options);
				$resource=$this->Survey_resource_model->select_single($resource_id);
				$this->set_response($resource, REST_Controller::HTTP_OK);
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




	//delete a single resource by resource id
	function external_resource_delete_delete($resource_id=null)
	{		
		$this->load->model("Survey_resource_model");		
		try{
			if(!is_numeric($resource_id)){
				throw new Exception("MISSING_PARAM: resource_id");
			}

			$this->Survey_resource_model->delete($resource_id);
			$this->set_response('DELETED', REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}




	//delete all resources by study
	function external_resource_delete_all_delete($sid=null)
	{		
		$this->load->model("Survey_resource_model");		
		try{
			if(!is_numeric($sid)){
				throw new Exception("MISSING_PARAM: sid");
			}

			$this->Survey_resource_model->delete_all_survey_resources($sid);
			$this->set_response('DELETED', REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}



	function external_resources_fix_links_get($sid=null)
	{
		$this->load->model("Survey_resource_model");		
		try{
			if(!is_numeric($sid)){
				throw new Exception("MISSING_PARAM: sid");
			}

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


	//list files attached to a study
	function external_resource_files_get($sid=null)
	{
		try{
			if(!is_numeric($sid)){
				throw new Exception("MISSING_PARAM: sid");
			}

			$this->load->model('Survey_resource_model');
			$files=$this->Survey_resource_model->get_files_array($sid);
			$this->set_response($files, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}	
	}


	//upload external resource file
	function external_resource_upload_file_post($sid=null)
	{
		$this->load->model('Survey_resource_model');
		
		try{
			if(!$sid){
				throw new Exception("SID_NOT_SET");
			}

			$result=$this->Survey_resource_model->upload_file($sid,$file_field_name='file');
			$uploaded_file_name=$result['file_name'];
			$uploaded_path=$result['full_path'];			

			$output=array(
				'status'=>'success',
				'uploaded_file_name'=>$uploaded_file_name,
				'base64'=>base64_encode($uploaded_file_name)
			);

			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	

	//delete a file
	function external_resource_delete_file_post($sid=null)
	{
		$this->load->model('Survey_resource_model');		
		$base64_filename=$this->input->post("base64_name");		

		try{
			if(!$sid){
				throw new Exception("PARAM_NOT_SET: SID");
			}

			if(!$base64_filename || trim($base64_filename)==""){
				throw new Exception("PARAM_NOT_SET: base64_name");
			}

			$this->Survey_resource_model->delete_file($sid, $base64_filename);		

			$output=array(
				'status'=>'success'
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


	function external_resource_download_file_get($sid=null,$base64_filename=null)
	{
		$this->load->model('Survey_resource_model');		

		try{
			if(!$sid){
				throw new Exception("PARAM_NOT_SET: SID");
			}

			if(!$base64_filename || trim($base64_filename)==""){
				throw new Exception("PARAM_NOT_SET: base64_name");
			}

			return $this->Survey_resource_model->download_file($sid, $base64_filename);
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
		
	
	/*
	- [done] list studies by current user
	- [done] list recent studies
	- [done] delete study
	- replace study (replace ddi)
	- refresh metadata
	- [done] set collection ownership / transfer ownership
	- [done]publish/unpublish
	- get/set/del tags
	- get/set/del notes
	- [done] get metadata
	- citations - list/attach/delete
	- licensed data requests - list/update/delete
	- view keywords for search
	- set custom keywords for search
	- related studies - list/add/update/delete
	- get/set custom key/values for metadata
	- study options
		-- data access type
		-- publish/unpublish
		-- study links
		-- indicators links
		-- aliases
		-- study relationships
		-- generate pdf
		-- delete pdf
		-- upload files
		-- resources
		--- add/edit/delete resource with ability to upload a file
		--- list resources
		--- import rdf
		--- fix links
		---
	- external resources
		-- list
			-- indicate if a resource link is broken
		-- [done]  add
		-- [done]  edit
		-- import rdf
		-- export rdf
		-- [done]  delete
		-- delete all resources
		--  [done] fix links
	- external resources files
		-- [done] list files
		-- [done]  upload
		-- [done]  delete
		-- [done]  download

	*/
	
	
}
