<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Tables extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct(); 
		$this->load->helper("date");
		$this->load->helper("file_helper");
		$this->load->model("Data_table_mongo_model");
		$this->load->model("Data_table_model");
		$this->load->model("Survey_data_api_model");
	}

	//list all tables with count
	function index_get($db_id=null)
	{
		try{
			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}

			$table_types=(array)$this->Data_table_mongo_model->get_table_types_list($db_id);
			$table_storage_info=(array)$this->Data_table_mongo_model->get_tables_list();

			$output=array();

			foreach($table_types as $table_id=>$table)
			{
				if (array_key_exists($table_id,$table_storage_info)){
					$table_types[$table_id]['rows_count']=$table_storage_info[$table_id]['count'];
					$table_types[$table_id]['storage_size']=$table_storage_info[$table_id]['storageSize'].'M';
				}

				if(isset($table['table_id']) && isset($table['db_id'])){
				$table_types[$table_id]['_links']= array(
					"info" => array(
						"href" => site_url('/api/tables/info/'.$table['db_id'].'/'.$table['table_id'])
					),
					"data" => array(
						"href" => site_url('/api/tables/data/'.$table['db_id'].'/'.$table['table_id'])
					)
				);
				}
			}
			
			foreach($table_storage_info as $table_id=>$table){
				if (!array_key_exists($table_id,$table_types)){
					$table_types[$table_id]=$table;
				}
			}
			
			$response=array(
                'status'=>'success',
				'tables'=>$table_types,
				//'tables_storage'=>$table_storage_info
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
	 * Return a list of all collections in the database
	 * 
	 * 
	 */
	function collection_tables_get()
	{
		try{
			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}

			$table_storage_info=$this->Data_table_mongo_model->get_tables_list();

			if(isset($options['format']) && $options['format']=='csv'){

				if ($this->input->get("disposition")=='inline'){
					$this->export_data_to_csv(array_values($table_storage_info));
					die();
				}
				
				header('Content-Disposition: attachment; filename=tables-list.csv');
				$response=$table_storage_info;
			}
			else{
				$response=array(
					'status'=>'success',
					'tables_storage'=>$table_storage_info
				);
			}
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

	function list_get($db_id=null)
	{
		$this->index_get($db_id);
	}


	/**
	 * 
	 * 
	 * Get table summary
	 * 
	 */
	function info_get($db_id=null,$table_id=null)
	{
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();			
			
			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			$result=$this->Data_table_mongo_model->get_table_info($db_id,$table_id);

			//remove import_progress
			$metadata = $result['table_type'];
			if (isset($metadata['import_progress'])) {
				unset($metadata['import_progress']);
			}

			$result=array(
				//'storageUnit'=>'M',
				//'size'=>$result['size'],
				'count'=>$result['count'],
				//'storageSize'=>$result['storageSize'],
				//'nindexes'=>$result['nindexes'],
				//'indexes'=>$result['indexDetails'],
				//'indexNames'=>array_keys((array)$result['indexDetails']),
				'metadata'=>$metadata
			);
			
			$response=array(
				'status'=>'success',
                'result'=>$result
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
	 * Get a list of all databases
	 * 
	 */
	function databases_get()
	{
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			$output=$this->Data_table_mongo_model->get_database_info();
			
			$response=array(
				'status'=>'success',
                'result'=>$output
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
	 * Get list of indexes for a table
	 * 
	 */
	function indexes_get($db_id=null,$table_id=null)
	{
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}
			
			$output=$this->Data_table_mongo_model->get_collection_indexes($db_id,$table_id);
			
			$response=array(
				'status'=>'success',
                'result'=>$output
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
	 * Create index for a collection 
	 * 
	 */
	function indexes_post($db_id=null,$table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

			$index_fields=isset($options['index_fields']) ? $options['index_fields'] : '';

			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			if(!$index_fields){
				throw new Exception("MISSING_PARAM:: index_fields");
			}
			
			$output=$this->Data_table_mongo_model->create_collection_index($db_id,$table_id,$index_fields);
			
			$response=array(
				'status'=>'success',
                'result'=>$output
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
	 * Create text index for a collection 
	 * 
	 */
	function text_index_post($db_id=null,$table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

			$index_fields=isset($options['index_fields']) ? $options['index_fields'] : '';

			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			if(!$index_fields){
				throw new Exception("MISSING_PARAM:: index_fields");
			}
			
			$output=$this->Data_table_mongo_model->create_collection_text_index($db_id,$table_id,$index_fields);
			
			$response=array(
				'status'=>'success',
                'result'=>$output
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
	 * Create index for a collection 
	 * 
	 */
	function indexes_delete($db_id=null,$table_id=null,$index_name=null)
	{
		$this->is_admin_or_die();
		
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			if(!$index_name){
				throw new Exception("MISSING_PARAM:: index_name");
			}
			
			$output=$this->Data_table_mongo_model->delete_collection_index($db_id,$table_id,$index_name);
			
			$response=array(
				'status'=>'success',
                'result'=>$output
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
	 * Get table data
	 * 
	 * 
	 */
	function data_get($db_id=null,$table_id=null,$limit=100,$offset=0)
	{
		try{
			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();			
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}

			if ($this->input->get("offset") > 1){
				$offset=(int)$this->input->get("offset");
			}

			if ($this->input->get("limit") > 1){
				$limit=(int)$this->input->get("limit");
			}

			//$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			$response=$this->Data_table_mongo_model->get_table_data($db_id,$table_id,$limit,$offset,$options);
			
			if(isset($options['format']) && $options['format']=='csv'){

				if ($this->input->get("disposition")=='inline'){
					$this->export_data_to_csv($response['data']);
					die();
				}
				
				header('Content-Disposition: attachment; filename=table-'."{$db_id}-{$table_id}-{$offset}".'.csv');
				$response=$response['data'];
			}

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'error'=>$this->Data_table_model->get_db_error()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Export data to CSV and JSON
	 * 
	 * 
	 */
	function export_get($db_id=null,$table_id=null)
	{
		try{
			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();			
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}

			$user_id=$this->get_api_user_id();

			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			$data_format=isset($options['format']) ? $options['format'] : 'json';

			if (!in_array($data_format,array('json','csv'))){
				throw new Exception("Invalid format:: $data_format. Supported formats are: json, csv");
			}

			$this->Data_table_mongo_model->export_data($db_id,$table_id,$data_format,$options);
			die();
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	function aggregate_get($db_id=null,$table_id=null,$limit=100,$offset=0)
	{
		try{
			
			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();			
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}

			if ($this->input->get("offset") > 1){
				$offset=(int)$this->input->get("offset");
			}

			if ($this->input->get("limit") > 1){
				$limit=(int)$this->input->get("limit");
			}

			//$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			$response=$this->Data_table_mongo_model->get_table_aggregate($db_id,$table_id,$limit,$offset,$options);
			
			if(isset($options['format']) && $options['format']=='csv'){

				if ($this->input->get("disposition")=='inline'){
					$this->export_data_to_csv($response['data']);
					die();
				}
				
				header('Content-Disposition: attachment; filename=table-'."{$db_id}-{$table_id}-{$offset}".'.csv');
				$response=$response['data'];
			}

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'error'=>$this->Data_table_model->get_db_error()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	private function export_data_to_csv($data,$filename='export',$delimiter = ',',$enclosure = '"')
	{
		header("Content-Type: text/plain");
		$fp = fopen("php://output", 'w');
		fputcsv($fp,array_keys($data[0]),$delimiter,$enclosure);
		foreach ($data as $row) {
			foreach($row as $key=>$value){
				if (is_array($value)){
					$row[$key]=implode("|",$value);
				}
			}
			fputcsv($fp, $row,$delimiter,$enclosure);
		}

		fclose($fp);
	}



	/**
	 * 
	 * 
	 * Create/insert table rows
	 * 
	 * 
	 * @db_id - database id
	 * @table_id - table id
	 * 
	 * @options - array of rows to insert
	 * 
	 */
	function insert_post($db_id=NULL, $table_id=NULL)
	{
		$this->is_admin_or_die();

		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

			if (!$db_id){
				throw new exception("Missing Param:: dbId");
			}

			if (!$table_id){
				throw new exception("Missing Param:: tableId");
			}

			//check if a single row input is provided or a list of rows
			$key=key($options);

			//convert to list of a list
			if(!is_numeric($key)){
				$tmp_options=array();
				$tmp_options[]=$options;
				$options=null;
				$options=$tmp_options;
			}
			
			$result=$this->Data_table_mongo_model->table_batch_insert($db_id,$table_id,$options);   

			$response=array(
                'status'=>'success',
				"output"=>$result				
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
		catch(Error $e){
			$error_output=array(
				'status'=>'Error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	

	/**
	 * 
	 * 
	 * Upload CSV file and create table definition
	 * 
	 * @db_id - database id
	 * @table_id - table id	 
	 * @file - [FILE] - CSV file to upload
	 * 
	 * Form data for table definition (optional):
	 * @title - [String] - table title (optional, defaults to "{db_id} - {table_id}")
	 * @description - [String] - table description (optional, defaults to "N/A")
	 * @delimiter - [String] - CSV delimiter (optional, defaults to comma)
	 * @indicators - [JSON] - indicators array (optional)
	 * @feature_1 to @feature_9 - [JSON] - feature objects (optional)
	 * 
	 * Note: This function only uploads files and creates table definitions
	 * Note: Use import_post to import CSV data into the table
	 * Note: CSV file is kept for later import via import_post
	 * 
	 */
	function upload_post($db_id,$table_id)
	{
		$this->is_admin_or_die();
		
		try{			
			if (!$db_id){
				throw new exception("Missing Param:: dbId");
			}

			if (!$table_id){
				throw new exception("Missing Param:: tableId");
			}

			$uploaded_file=$this->upload_file('datafiles/'.$db_id);
			$uploaded_file_path=$uploaded_file['full_path'];

			$user_id=$this->get_api_user_id();			

			if (!file_exists($uploaded_file_path)){
				throw new Exception("file was not uploaded");
			}

			$table_dir = 'datafiles/'.$db_id.'/'.$table_id;
			$is_zip = $this->is_zip_file($uploaded_file_path);
			
			if($is_zip){
				//extract zip to table folder
				$uploaded_file_path = $this->get_file_from_zip($uploaded_file_path, $table_dir);
			} else {
				//move csv to table folder
				$this->move_csv_to_table_dir($uploaded_file_path, $table_dir);
				$uploaded_file_path = $table_dir.'/'.basename($uploaded_file_path);
			}

			$partial_file_path = str_replace('datafiles/', '', $uploaded_file_path);			
			$form_data = $this->collect_form_data();

			//delete original uploaded zip file, keep csv
			if($is_zip && file_exists($uploaded_file['full_path'])){
				unlink($uploaded_file['full_path']);				
			}
						
			$definition_result = $this->Data_table_mongo_model->upsert_table_type($db_id, $table_id, $partial_file_path, $form_data);			

			$response=array(
                'status'=>'success',
				'file_path'=>$partial_file_path,  // Partial path for import_post
				'definition_updated' => $definition_result['was_existing'] ? $definition_result['result'] : 0,
				'definition_created' => $definition_result['was_existing'] ? 0 : $definition_result['result'],
				'action' => $definition_result['action'],
				'csv_uploaded_at' => date('Y-m-d H:i:s'),
				'import_status' => 'ready',
				'links' => array(
					'import' => site_url() . '/api/tables/import/' . $db_id . '/' . $table_id
				),
				'message' => 'File uploaded and table definition ' . $definition_result['action'] . ' - import progress reset, ready for new import'
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
	 * Validate import consistency before processing
	 * 
	 * @param string $db_id Database ID
	 * @param string $table_id Table ID
	 * @param int $start_row Starting row for import
	 * @param array $table_definition Table definition data
	 * @throws Exception if validation fails
	 */

	/**
	 * Import CSV data into table using chunked processing
	 * 
	 * @param string $db_id Database ID
	 * @param string $table_id Table ID
	 * @param int $max_rows Maximum rows per batch (default: 10000, max: 50000)
	 */
	function import_post($db_id=null, $table_id=null)
	{
		$this->is_admin_or_die();

		try {
			$options = $this->raw_json_input();

			// Get db_id and table_id from options if not provided
			if (!$db_id) {
				$db_id = $options['db_id'] ?? null;
				$table_id = $options['table_id'] ?? null;
			}

			// Validate required parameters
			if (!$db_id || !$table_id) {
				throw new Exception("Missing required parameters: dbId and tableId");
			}

			// Process the import request through the model
			$result = $this->Data_table_mongo_model->process_import_request($db_id, $table_id, $options);

			$this->set_response($result, REST_Controller::HTTP_OK);

		} catch (Exception $e) {
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage(),
				'action_required' => $this->determine_action_required($e->getMessage())
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Determine action required based on error message
	 */
	private function determine_action_required($message)
	{
		if (strpos($message, 'already contains') !== false) {
			return 'delete_data_first';
		}
		if (strpos($message, 'inconsistency') !== false) {
			return 'reset_import';
		}
		return null;
	}

	
	/**
	 * 
	 * 
	 * Rename collection	 
	 * 
	 */
	function rename_collection_post()
	{
		$this->is_admin_or_die();

		try{
			$user_id=$this->get_api_user_id();
			$options=$this->raw_json_input();

			if (!isset($options['rename_collections']) && !is_array($options['rename_collections']) ){
				throw new Exception("rename_collections not provided or is not an array");
			}

			$result=array();
			foreach($options['rename_collections'] as $rename_collection){

				if (!isset($rename_collection['old'])){
					throw new exception("Missing Param:: old");
				}
				
				if (!isset($rename_collection['new'])){
					throw new exception("Missing Param:: new");
				}

				$result[]=$this->Data_table_mongo_model->rename_collection($rename_collection['old'], $rename_collection['new']);
				//$result[]=$rename_collection;
			}
			
			$response=array(
                'status'=>'success',
				"result"=>$result,
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
	 * Create table definition
	 * 
	 * @db_id - database id
	 * @table_id - table id
	 * 
	 * @options - metadata for table
	 * 
	 *  - title, description, documentation links, dimensions, etc
	 * 
	 */
	function create_table_post($db_id=NULL, $table_id=NULL)
	{
		$this->is_admin_or_die();

		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			if (!$db_id){
				throw new exception("Missing Param:: dbId");
			}

			if (!$table_id){
				throw new exception("Missing Param:: tableId");
			}

            $result=$this->Data_table_mongo_model->create_table($db_id,$table_id,$options);			

			$response=array(
                'status'=>'success',
				'result'=>$result
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


	function delete_delete($db_id=null,$table_id=null)
	{
		return $this->delete_post($db_id, $table_id);
	}

	function delete_post($db_id=null,$table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			$delete_definition = isset($options['delete_definition']) ? $options['delete_definition'] : false;

			if (!$db_id){
				throw new exception("Missing Param:: dbId");
			}

			if (!$table_id){
				throw new exception("Missing Param:: tableId");
			}

            $data_result = $this->Data_table_mongo_model->delete_table_data($db_id, $table_id);
            
            $this->Data_table_mongo_model->update_import_progress($db_id, $table_id, array(
                'last_processed_row' => -1,
                'total_rows_processed' => 0,
                'import_status' => 'ready',
                'import_started_at' => null,
                'import_completed_at' => null,
                'last_import_at' => null
            ));
            
            $definition_result = 0;
            if ($delete_definition) {
                $definition_result = $this->Data_table_mongo_model->delete_table_type($db_id, $table_id);
            }

			$response = array(
                'status' => 'success',
                'data_deleted' => $data_result,
                'definition_deleted' => $definition_result,
                'import_progress_reset' => true,
                'message' => $delete_definition ? 
                    'Table data and definition deleted successfully, import progress reset' : 
                    'Table data deleted successfully, import progress reset (definition preserved)'
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
	 * Attach data table to a study
	 * 
	 */
	function attach_to_study_post()
	{
		$this->is_admin_or_die();

		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			if (!isset($options['db_id'])){
				throw new exception("Missing Param:: dbId");
			}

			if (!isset($options['table_id'])){
				throw new exception("Missing Param:: tableId");
			}

			if (!isset($options['sid'])){
				throw new exception("Missing Param:: sid");
			}

			$result=$this->Survey_data_api_model->insert($options);
			

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
	 * Detach data table from a study
	 * 
	 */
	function detach_from_study_post()
	{
		$this->is_admin_or_die();
		
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			if (!isset($options['db_id'])){
				throw new exception("Missing Param:: dbId");
			}

			if (!isset($options['table_id'])){
				throw new exception("Missing Param:: tableId");
			}

			if (!isset($options['sid'])){
				throw new exception("Missing Param:: sid");
			}

			$result=$this->Survey_data_api_model->detach($options['sid'],$options['db_id'],$options['table_id']);			

			$response=array(
				'status'=>'success',
				'result'=>$result
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
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * List of datassets attached to a study
	 * 
	 */
	function list_by_study_get($sid=null)
	{
		$this->is_authenticated_or_die();
		
		try{
			
			if (!$sid){
				throw new exception("Missing Param:: sid");
			}

			$result=$this->Survey_data_api_model->get_by_sid($sid);			

			$response=array(
				'status'=>'success',
				'result'=>$result
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
				'error'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}

	}


	/**
	 * 
	 * upload file
	 * 
	 **/ 
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

	/**
	 * Move CSV file to table-specific directory
	 */
	private function move_csv_to_table_dir($csv_file_path, $table_dir)
	{
		if (!file_exists($table_dir)) {
			mkdir($table_dir, 0777, true);
		}
		
		$filename = basename($csv_file_path);
		$new_path = $table_dir.'/'.$filename;
		
		if (!rename($csv_file_path, $new_path)) {
			throw new Exception("Failed to move CSV file to table directory");
		}
	}

	/**
	 * Collect form data for table definition
	 */
	private function collect_form_data()
	{
		$form_data = array(
			'title' => $this->input->post('title'),
			'description' => $this->input->post('description'),
			'indicators' => $this->input->post('indicators')
		);
		
		// Add features if provided
		for ($i = 1; $i <= 9; $i++) {
			$feature_key = 'feature_' . $i;
			$form_data[$feature_key] = $this->input->post($feature_key);
		}
		
		return $form_data;
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

	/**
	 * 
	 * 
	 * Get table schema (fields and data types)
	 * 
	 * @db_id - database id
	 * @table_id - table id
	 * 
	 */
	function schema_get($db_id=null,$table_id=null)
	{
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();			
			
			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			// Get field metadata from the separate data dictionary table
			$field_metadata = $this->Data_table_mongo_model->get_field_metadata($db_id, $table_id);
			
			if (!$field_metadata) {
				throw new Exception("No field metadata found for this table");
			}

			$response=array(
				'status'=>'success',
				'db_id' => $db_id,
				'table_id' => $table_id,
				'total_fields' => count($field_metadata),
				'schema' => $field_metadata
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
	 * Populate table schema by reading one row from the actual data collection
	 * and storing field information in the table_types collection
	 * 
	 * @db_id - database id
	 * @table_id - table id
	 * 
	 */
	function populate_schema_post($db_id=null,$table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();			
			
			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			// Get field names from actual data collection
			$field_names = $this->Data_table_mongo_model->get_data_field_names($db_id, $table_id);
			
			if (empty($field_names)) {
				throw new Exception("No data found in the collection to extract schema from");
			}

			// Create basic field metadata with just names
			$fields_metadata = array();
			foreach ($field_names as $field_name) {
				$fields_metadata[] = array(
					'name' => $field_name,
					'title' => $field_name, // Use field name as default title
					'dataType' => 'string', // Default data type
					'required' => false,    // Default to not required
					'description' => '',    // Empty description by default
					'created_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s')
				);
			}

			// Store the schema in table_types collection
			$result = $this->Data_table_mongo_model->update_table_schema($db_id, $table_id, $fields_metadata);
			
			if ($result === false) {
				throw new Exception("Failed to update table schema");
			}

			$response=array(
				'status'=>'success',
				'db_id' => $db_id,
				'table_id' => $table_id,
				'total_fields' => count($fields_metadata),
				'message' => 'Table schema populated successfully',
				'schema' => $fields_metadata
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

}	
