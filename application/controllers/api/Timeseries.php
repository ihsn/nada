<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Timeseries extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct(); 
		$this->load->helper("date");
		$this->load->model("Data_table_mongo_model");
		$this->load->model("Data_table_model");
		$this->load->model("Survey_data_api_model");
		$this->load->model("Timeseries_model");
		$this->load->model("Timeseries_tables_model");
	}


	public function status_get()
	{
		//$this->is_authenticated_or_die();
		$connected=$this->Timeseries_model->get_database_info();
		$response=array(
			'status'=>'success',			
			'connected'=>$connected
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	/**
	 * 
	 * 
	 * List of available timeseries
	 * 
	 */
	public function index_get($db_id=null, $series_id=null)
	{
		return $this->list_get($db_id, $series_id);
	}

	/**
	 * 
	 * 
	 * List all available timeseries
	 * 
	 * @db_id - database id
	 * @series_id - series_id
	 * 
	 */
	public function list_get($db_id=null, $series_id=null)
	{
		//$this->is_authenticated_or_die();

		try{
			$user_id=$this->get_api_user_id();
			$query_params=$this->input->get();
			$offset=$query_params['offset'] ?? 0;
			$limit=$query_params['limit'] ?? 100;
			$response=$this->Timeseries_tables_model->search($db_id, $series_id, $limit, $offset, $query_params);
			
			foreach($response['data'] as $key=>$item){				
				if (!isset($item['db_id']) || !isset($item['series_id'])){
					continue;
				}

				$db_id=$item['db_id'];
				$series_id=$item['series_id'];

				$response['data'][$key]['_links']=array(
					'info'=>site_url('api/timeseries/info/'.$db_id.'/'.$series_id),
					'data'=>site_url('api/timeseries/data/'.$db_id.'/'.$series_id),					
					'data_structure'=>site_url('api/timeseries/data_structure/'.$db_id.'/'.$series_id),
					'chart'=>site_url('data/chart?db_id='.$db_id.'&series_id='.$series_id)
				);				
			}

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Info for a single timeseries table
	 * 
	 * @db_id - database id
	 * @series_id - series_id
	 * 
	 */
	public function info_get($db_id=null, $series_id=null)
	{
		//$this->is_authenticated_or_die();

		try{

			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));

			$user_id=$this->get_api_user_id();
			$result=$this->Timeseries_tables_model->list_timeseries($db_id, $series_id);			

			$response=array(
				'status'=>'success',
				'data'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	

	function validate_data_structure_get($db_id=null, $series_id=null)
	{
		//$this->is_authenticated_or_die();

		try{
			$user_id=$this->get_api_user_id();
			$data_structure=$this->Timeseries_tables_model->get_single($db_id, $series_id);
			$response= $this->Timeseries_model->validate_data_structure($data_structure);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>"VALIDATION_ERRORS",
				'errors'=>$e->GetValidationErrors(),
				'data'=>$data_structure
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	function validate_data_structure_post($db_id=null, $series_id=null)
	{
		//$this->is_authenticated_or_die();

		try{
			$user_id=$this->get_api_user_id();
			$data_structure=$this->raw_json_input();
			$result= $this->Timeseries_model->validate_data_structure($data_structure);

			$response=array(
				'status'=>'success',
				'result'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>"VALIDATION_ERRORS",
				'errors'=>$e->GetValidationErrors(),
				'data'=>$data_structure
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


		

	private function validate_required_params($param_keys, $values)
	{
		foreach($param_keys as $key){
			if (!isset($values[$key])){
				throw new exception("Missing Param:: $key");
			}
		}		
	}

	/**
	 * 
	 * 
	 * Insert data into timeseries
	 * 
	 */
	public function insert_data_post($db_id=null, $series_id=null)
	{
		$this->is_authenticated_or_die();

		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));

			$result=$this->Timeseries_model->series_batch_insert($db_id, $series_id, $options);			

			$response=array(
				'status'=>'success',
				'result'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}



	/**
	 * 
	 * 
	 * Import series data from JSON or CSV file
	 * 
	 * @db_id - database id
	 * @series_id - series_id
	 * @file - [FILE] - CSV file to upload
	 * 
	 * @append - [Boolean] - querystring - append to existing table. Default is overwrite
	 * 
	 * Note: This will drop existing series data
	 * 
	 */
	function import_data_post($db_id,$series_id)
	{
		try{			
			if (!$db_id){
				throw new exception("Missing Param:: db_id");
			}

			if (!$series_id){
				throw new exception("Missing Param:: series_id");
			}

			$append=$this->input->get("append") == 'true' ? true : false;
			$uploaded_file=$this->upload_file('datafiles/'.$db_id);
			$uploaded_file_path=$uploaded_file['full_path'];

			$user_id=$this->get_api_user_id();

			if (!file_exists($uploaded_file_path)){
				throw new Exception("file was not uploaded");
			}

			if($this->is_zip_file($uploaded_file_path)){
				$unzip_path='datafiles/'.$db_id.'/'.$series_id;
				$uploaded_file_path=$this->get_file_from_zip($uploaded_file_path,$unzip_path);
			}

			if (!$append){
				//drop existing series data
				$this->Timeseries_model->series_delete($db_id, $series_id);
			}

			//validate csv columns match the series data structure
			//$series_data_structure=$this->Timeseries_tables_model->get_data_structure_columns($db_id, $series_id);

			//$csv_columns=$this->Timeseries_model->get_csv_columns($uploaded_file_path);
			$this->Timeseries_tables_model->validate_csv_data($db_id, $series_id, $uploaded_file_path);

			//import csv
			$result=$this->Timeseries_model->import_csv(
				$db_id,
				$series_id,
				$uploaded_file_path, 
				$this->input->post("delimiter")
			);

			//remove uploaded files
			$files=array($uploaded_file_path, $uploaded_file['full_path']);
			foreach($files as $file){
				if (!empty($file) && file_exists($file)){
					unlink($file);
				}
			}

			$response=array(
                'status'=>'success',
				'file_path'=>$uploaded_file_path,
				'rows_imported'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'files'=>$_FILES
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Delete timeseries data
	 * 
	 */
	public function delete_data_post($db_id=null, $series_id=null)
	{
		$this->is_authenticated_or_die();

		try{
			$user_id=$this->get_api_user_id();		
			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));
			$result=$this->Timeseries_model->series_delete($db_id, $series_id);			

			$response=array(
				'status'=>'success',
				'deleted'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function data_get($db_id=null, $series_id=null)
	{
		//$this->is_authenticated_or_die();

		try{
			$user_id=$this->get_api_user_id();
			$query_params=$this->input->get();
			$limit=$this->input->get("limit");
			$offset=$this->input->get("offset");

			if (!is_numeric($limit)){
				$limit=100;
			}

			if (!is_numeric($offset)){
				$offset=0;
			}


			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));
			$result=$this->Timeseries_model->series_data($db_id, $series_id, $limit ,$offset, $query_params);			

			$response=array(
				'status'=>'success',
				'data'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	function distinct_get($db_id=null, $series_id=null)
	{
		try{
			$user_id=$this->get_api_user_id();
			$query_field=$this->input->get("field");

			if (!$query_field){
				throw new Exception("Missing querystring param:: field");
			}

			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));
			$result=$this->Timeseries_model->get_series_distinct_values($db_id, $series_id, $query_field);

			$response=array(
				'status'=>'success',
				'data'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}	


	public function databases_get()
	{
		try{
			$user_id=$this->get_api_user_id();
			$result=$this->Timeseries_tables_model->get_distinct_pipeline($field='db_id', $filter=array());

			$response=array(
				'status'=>'success',
				'data'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * 
	 * Insert timeseries info
	 * 
	 * @db_id - database id
	 * @series_id - series id
	 * @options - options
	 * @overwrite - overwrite existing
	 * 
	 */
	public function create_post($db_id=null, $series_id=null)
	{
		$this->is_authenticated_or_die();

		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));
			$result= $this->Timeseries_model->validate_data_structure($options);
			$result=$this->Timeseries_tables_model->upsert($db_id, $series_id, $options, $overwrite=true);

			$response=array(
				'status'=>'success',
				'result'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>"VALIDATION_ERRORS",
				'errors'=>$e->GetValidationErrors()				
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	public function delete_post($db_id=null, $series_id=null)
	{
		$this->is_authenticated_or_die();

		try{
			$user_id=$this->get_api_user_id();		
			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));
			$result=$this->Timeseries_tables_model->delete($db_id, $series_id);			

			$response=array(
				'status'=>'success',
				'deleted'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * 
	 * Get data structure for a single timeseries
	 * 
	 * @db_id - database id
	 * @series_id - series id
	 * 
	 * querystring params:
	 * 	- column_names_only - [Boolean] - return only column names
	 *  - columns - [String] - comma separated list of columns to return
	 *  - code_lists_only - [Boolean] - return code lists 
	 *  - code_list - [String] - return code list for a specific column
	 *  
	 * 
	 * 
	 * 
	 * @return array
	 * 
	 */
	public function data_structure_get($db_id=null, $series_id=null)
	{
		try{		
			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));
			$params=$this->input->get();
			$result=$this->Timeseries_tables_model->get_data_structure($db_id, $series_id, $params);

			$response=array(
				'data_structure'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	public function data_structure_columns_get($db_id=null, $series_id=null)
	{
		try{		
			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));			
			$result=$this->Timeseries_tables_model->get_data_structure_columns($db_id, $series_id);

			$response=array(
				'data_structure'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * Get a list of geographies in a series
	 * 
	 */
	public function geographies_get($db_id=null, $series_id=null)
	{
		try{		
			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));
			
			$query_field=$this->Timeseries_tables_model->get_data_structure_column_by_type($db_id,$series_id,'geography');
			
			if (!$query_field){
				throw new Exception("Column type not found");
			}


			$field_name=$query_field['name'];

			//get unqiue values
			$values=$this->Timeseries_model->get_series_distinct_values($db_id, $series_id, $field_name);

			$response=array(
				'column_info'=>$query_field,
				'data'=>$values
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * Get a list of time_periods in a series
	 * 
	 */
	public function time_periods_get($db_id=null, $series_id=null)
	{
		try{		
			$this->validate_required_params(array("db_id","series_id"), array("db_id"=>$db_id, "series_id"=>$series_id));
			
			$query_field=$this->Timeseries_tables_model->get_data_structure_column_by_type($db_id,$series_id,'time_period');
			
			if (!$query_field){
				throw new Exception("Column type not found");
			}


			$field_name=$query_field['name'];

			//get unqiue values
			$values=$this->Timeseries_model->get_series_distinct_values($db_id, $series_id, $field_name);

			$response=array(
				'column_info'=>$query_field,
				'data'=>$values
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	
	




	private function is_zip_file($file_name)
	{
		$file_ext=pathinfo($file_name,PATHINFO_EXTENSION);

		if ($file_ext=='zip'){
			return true;
		}

		return false;
	}

	private function unzip_file($zip_file,$output_path)
	{
		$zip = new ZipArchive;
		if ($zip->open($zip_file) === TRUE) {
			$zip->extractTo($output_path);
			$zip->close();
		} else {
			throw new Exception("Failed to unzip file: ". $zip_file);
		}
	}

	private function get_file_from_zip($zip_file, $output_path)
	{
		$this->unzip_file($zip_file,$output_path);

		$base_name=pathinfo($zip_file,PATHINFO_FILENAME);

		$files=array(
			$output_path.'/'.$base_name.'.csv',
			$output_path.'/'.$base_name.'.txt',
			$output_path.'/'.$base_name.'.CSV',
			$output_path.'/'.$base_name.'.TXT'
		);

		foreach($files as $file){
			if(file_exists($file)){
				return $file;
			}
		}

		throw new Exception("CSV file not found in ZIP");
	}

	private function upload_file($upload_path='datafiles')
	{		
		if(!isset($_FILES['file'])){
			throw new Exception("FILE NOT PROVIDED");
		}

		if(!file_exists($upload_path)){
			mkdir($upload_path,0777,true);
		}

		$file_name=$_FILES['file'];
		$this->load->library('upload');
	
		$config['upload_path'] = $upload_path;
		$config['overwrite'] = true;
		$config['encrypt_name']=false;
		$config['allowed_types'] = 'txt|csv|zip';
		
		$this->upload->initialize($config);
		
		$upload_result=$this->upload->do_upload('file');

		if(!$upload_result){
			$error = $this->upload->display_errors();            
			throw new Exception("UPLOAD_FAILED::".$upload_path. ' - error:: '.$error);
		}

		$upload_data = $this->upload->data();			
		return $upload_data;
	}

}