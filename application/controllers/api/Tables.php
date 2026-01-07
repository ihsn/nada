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

			// Pagination parameters
			$limit = 15; // Default limit
			$offset = 0; // Default offset
			
			if ($this->input->get("limit") && is_numeric($this->input->get("limit")) && $this->input->get("limit") > 0){
				$limit = (int)$this->input->get("limit");
				// Set max limit
				if ($limit > 100) {
					$limit = 100;
				}
			}

			if ($this->input->get("offset") && is_numeric($this->input->get("offset")) && $this->input->get("offset") >= 0){
				$offset = (int)$this->input->get("offset");
			}

			$table_types=(array)$this->Data_table_mongo_model->get_table_types_list($db_id);
			$table_storage_info=(array)$this->Data_table_mongo_model->get_tables_list();

			// Check if user is authenticated
			$is_authenticated = $this->get_api_user_id() !== false;

			$output=array();

			foreach($table_types as $table_id=>$table)
			{
				if (array_key_exists($table_id,$table_storage_info)){
					$table_types[$table_id]['rows_count']=$table_storage_info[$table_id]['count'];
					// Only include storage_size and index info if user is authenticated
					if ($is_authenticated) {
						$table_types[$table_id]['storage_size']=$table_storage_info[$table_id]['storageSize'].'M';
						$table_types[$table_id]['nindexes']=$table_storage_info[$table_id]['nindexes'];
						$table_types[$table_id]['indexNames']=$table_storage_info[$table_id]['indexNames'];
					}
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
			
			// Filter out entries that don't have both db_id and table_id
			$filtered_tables = array();
			foreach($table_types as $table_id=>$table){
				if(isset($table['db_id']) && isset($table['table_id'])){
					$filtered_tables[$table_id] = $table;
				}
			}
			
			// Get total count before pagination
			$total = count($filtered_tables);
			
			// Apply pagination
			$paginated_tables = array_slice($filtered_tables, $offset, $limit, true);
			
			$response=array(
                'status'=>'success',
				'tables'=>$paginated_tables,
				'total'=>$total,
				'limit'=>$limit,
				'offset'=>$offset,
				'count'=>count($paginated_tables)
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

	function list_get($db_id=null)
	{
		$this->index_get($db_id);
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
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');
			$result=$this->Data_table_mongo_model->get_table_info($db_id,$table_id);

			$metadata = $result['table_type'];

			// Remove fields
			$remove_fields = array('import_progress', 'last_imported_at', 'csv_file_path', 'csv_uploaded_at');

			foreach($remove_fields as $field){
				if (isset($metadata[$field])){
					unset($metadata[$field]);
				}
			}

			// Include data_dictionary if requested via query parameter
			$include_data_dictionary = $this->input->get('data_dictionary');
			if ($include_data_dictionary === 'true' || $include_data_dictionary === true) {
				$fields = $this->Data_table_mongo_model->get_table_fields($db_id, $table_id);
				if ($fields && is_array($fields) && count($fields) > 0) {
					$metadata['data_dictionary'] = $fields;
				} else {
					$metadata['data_dictionary'] = array();
				}
			}

			$result=array(
				'count'=>$result['count'],
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

			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');
			
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
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

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
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

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
			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

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
	 * Delete all indexes in a collection (except _id_)
	 * 
	 * 
	 */
	function indexes_delete_all_post($db_id=null,$table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');
			
			$output=$this->Data_table_mongo_model->delete_all_collection_indexes($db_id,$table_id);
			
			$response=array(
				'status'=>'success',
                'result'=>$output,
				'message'=>"Deleted {$output['indexes_dropped']} index(es). {$output['indexes_remaining']} index(es) remaining (_id_ is preserved)."
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

			$user_id=$this->get_api_user_id();			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

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
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Download table data as CSV, if exists
	 * 
	 * 
	 */
	function download_get($db_id=null, $table_id=null)
	{
		try{
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');
			$this->Data_table_mongo_model->download_csv_file($db_id, $table_id);
			die();
		}
		catch(Exception $e){
			log_message('error', 'Tables::download_get error: '.$e->getMessage());
			$error_output=array(
				'status'=>'failed',
				'message'=>'File is not available'
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Bulk export data to CSV and JSON
	 * 
	 * 
	 */
	function export_get($db_id=null,$table_id=null)
	{
		try{
			$this->is_admin_or_die();

			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();			
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}

			$user_id=$this->get_api_user_id();

			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

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

			$user_id=$this->get_api_user_id();
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

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
				'message'=>$e->getMessage()
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

			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

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
			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

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
			
			// Collect form data (title and description only)
			$form_data = array(
				'title' => $this->input->post('title', true),
				'description' => $this->input->post('description', true)
			);

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
	 * Import CSV data into table using chunked processing
	 * 
	 * @param string $db_id Database ID
	 * @param string $table_id Table ID
	 */
	function import_post($db_id=null, $table_id=null)
	{
		$this->is_admin_or_die();

		try {
			$options = $this->raw_json_input();
			if (!is_array($options)) {
				$options = array();
			}

			// Get db_id and table_id from options if not provided as URL parameters
			if (!$db_id) {
				$db_id = $options['db_id'] ?? null;
				$table_id = $options['table_id'] ?? null;
			}

			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

			// Process import
			$result = $this->Data_table_mongo_model->process_import_request($db_id, $table_id, $options);

			$this->set_response($result, REST_Controller::HTTP_OK);

		} catch (Exception $e) {
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
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
			
			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

			// Extract fields/data_dictionary if provided
			$fields = null;
			if (isset($options['fields']) && is_array($options['fields'])) {
				$fields = $options['fields'];
				unset($options['fields']); // Remove from table metadata
			} elseif (isset($options['data_dictionary']) && is_array($options['data_dictionary'])) {
				$fields = $options['data_dictionary'];
				unset($options['data_dictionary']); // Remove from table metadata
			}

            // Create table metadata
			$result = $this->Data_table_mongo_model->create_table($db_id, $table_id, $options);
			
			// Create field definitions if provided
			$fields_created = 0;
			if ($fields && !empty($fields)) {
				foreach ($fields as $field_metadata) {
					try {
						// Ensure field_metadata has required fields
						if (!isset($field_metadata['name'])) {
							continue; // Skip fields without name
						}
						
						// Create field metadata
						$field_result = $this->Data_table_mongo_model->create_field_metadata($db_id, $table_id, $field_metadata);
						if ($field_result > 0) {
							$fields_created++;
						}
					} catch (Exception $e) {
						// Log error but continue with other fields
						log_message('error', "Failed to create field {$field_metadata['name']}: " . $e->getMessage());
					}
				}
			}

			$response = array(
                'status' => 'success',
				'result' => $result,
				'fields_created' => $fields_created,
				'message' => $fields_created > 0 ? 
					"Table created with {$fields_created} field(s)" : 
					"Table created successfully"
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
	 * Update table metadata
	 * 
	 * PUT /api/tables/update_table/{db_id}/{table_id}
	 * 
	 * @db_id - database id
	 * @table_id - table id
	 * 
	 * @options - metadata to update (title, description, etc.)
	 * 
	 */
	function update_table_put($db_id=NULL, $table_id=NULL)
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

			// Prepare update data - only include metadata fields
			$update_data = array();
			if (isset($options['title'])) {
				$update_data['title'] = $options['title'];
			}
			if (isset($options['description'])) {
				$update_data['description'] = $options['description'];
			}
			
			// Update metadata in table_types collection
			$result = $this->Data_table_mongo_model->update_table_type($db_id, $table_id, $update_data);

			$response=array(
                'status'=>'success',
				'message' => 'Table information updated successfully',
				'modified_count' => $result
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
	 * Export table definition (metadata and field definitions)
	 * 
	 * GET /api/tables/{db_id}/{table_id}/export_definition
	 * 
	 * @db_id - database id
	 * @table_id - table id
	 * 
	 * Returns JSON with table metadata and all field definitions
	 */
	function export_definition_get($db_id=null, $table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			$user_id=$this->get_api_user_id();
			
			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');
			
			// Get table metadata
			$table_type = $this->Data_table_mongo_model->get_table_type($db_id, $table_id);
			if (!$table_type) {
				throw new Exception("Table not found: {$db_id}/{$table_id}");
			}
			
			// Get all field definitions
			$fields = $this->Data_table_mongo_model->get_table_fields($db_id, $table_id);
			
			// Get indexes
			$indexes = $this->Data_table_mongo_model->get_collection_indexes($db_id, $table_id);
			
			// Build export structure
			$export = array(
				'version' => '1.0',
				'exported_at' => date('Y-m-d H:i:s'),
				'table_metadata' => array(
					'db_id' => $table_type['db_id'] ?? $db_id,
					'table_id' => $table_type['table_id'] ?? $table_id,
					'title' => $table_type['title'] ?? null,
					'description' => $table_type['description'] ?? null,
					'created_at' => $table_type['created_at'] ?? null,
					'updated_at' => $table_type['updated_at'] ?? null,
				),
				'fields' => $fields ?: array(),
				'indexes' => $indexes ?: array()
			);
			
			// Remove import_progress and other internal fields from metadata if present
			if (isset($table_type['import_progress'])) {
				unset($table_type['import_progress']);
			}
			
			// Add any additional metadata fields (indicators, features, etc.)
			$additional_metadata = array();
			$exclude_fields = array('_id', 'db_id', 'table_id', 'title', 'description', 'created_at', 'updated_at', 'csv_file_path', 'csv_uploaded_at', 'import_progress');
			foreach ($table_type as $key => $value) {
				if (!in_array($key, $exclude_fields)) {
					$additional_metadata[$key] = $value;
				}
			}
			if (!empty($additional_metadata)) {
				$export['table_metadata']['additional'] = $additional_metadata;
			}
			
			// JSON download headers
			$filename = "table_definition_{$db_id}_{$table_id}_" . date('Y-m-d') . ".json";
			header('Content-Type: application/json');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			
			$response = array(
				'status' => 'success',
				'definition' => $export
			);
			
			echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			exit;
			
		} catch(Exception $e){
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage()
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
                'byte_offset_end' => 0,
                'total_rows_processed' => 0,
                'import_status' => 'ready',
                'import_started_at' => null,
                'import_completed_at' => null,
                'last_import_at' => null,
                'progress_percent' => 0
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
	 * Accepts either 'idno' or 'sid' parameter
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

			// Accept either idno or sid
			if (!isset($options['idno']) && !isset($options['sid'])){
				throw new exception("Missing Param:: idno or sid");
			}

			// If sid is provided, get idno from it
			if (isset($options['sid']) && !isset($options['idno'])){
				$this->load->model('Dataset_model');
				$idno = $this->Dataset_model->get_idno($options['sid']);
				if (!$idno){
					throw new exception("Study ID not found: " . $options['sid']);
				}
				$options['idno'] = $idno;
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
	 * Get list of studies attached to a table
	 * 
	 * GET /api/tables/{db_id}/{table_id}/studies
	 * 
	 */
	function studies_get($db_id=null, $table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			$user_id=$this->get_api_user_id();
			
			if (!$db_id){
				throw new exception("Missing Param:: dbId");
			}

			if (!$table_id){
				throw new exception("Missing Param:: tableId");
			}

			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

			$result = $this->Survey_data_api_model->get_by_table($db_id, $table_id);

			$response=array(
				'status'=>'success',
				'studies'=>$result,
				'total'=>count($result)
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
	 * Get all fields for a table
	 * 
	 * GET /api/tables/{db_id}/{table_id}/fields
	 * 
	 * @db_id - database id
	 * @table_id - table id
	 * 
	 */
	function fields_get($db_id=null,$table_id=null)
	{
		try{
			$options=$this->raw_json_input();
			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

			// Get field metadata from table_dictionary collection
			$fields = $this->Data_table_mongo_model->get_table_fields($db_id, $table_id);
			
			// Return empty array if no fields found (instead of throwing error)
			if (!$fields) {
				$fields = [];
			}
			
			$response=array(
				'status'=>'success',
				'db_id' => $db_id,
				'table_id' => $table_id,
				'total_fields' => count($fields),
				'fields' => $fields
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
	 * Get single field metadata
	 * 
	 * GET /api/tables/{db_id}/{table_id}/fields/{field_name}
	 */
	function field_get($db_id=null, $table_id=null, $field_name=null)
	{
		try{
			$user_id=$this->get_api_user_id();
			
			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

			if(!$field_name){
				throw new Exception("MISSING_PARAM:: field_name");
			}
			
			$field = $this->Data_table_mongo_model->get_field_metadata($db_id, $table_id, $field_name);
			
			if (!$field) {
				throw new Exception("Field not found");
			}
			
			$response=array(
				'status'=>'success',
				'field' => $field
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
	 * Create or update field metadata (upsert)
	 * 
	 * POST /api/tables/{db_id}/{table_id}/fields
	 * 
	 * Body must include 'name' field.
	 * If field exists, it will be updated. If not, it will be created.
	 */
	function fields_post($db_id=null, $table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			$field_data = $this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

			if(!isset($field_data['name'])){
				throw new Exception("MISSING_PARAM:: name");
			}
			
			// Check if field exists to determine if this is create or update
			$existing_field = $this->Data_table_mongo_model->get_field_metadata($db_id, $table_id, $field_data['name']);
			$is_update = ($existing_field !== null);
			
			// Set defaults for new fields only
			if (!$is_update) {
				$field_data['label'] = $field_data['label'] ?? $field_data['name'];
				$field_data['data_type'] = $field_data['data_type'] ?? 'string';
				$field_data['column_type'] = $field_data['column_type'] ?? null;
			}
			
			// Use create_field_metadata which does upsert
			$result = $this->Data_table_mongo_model->create_field_metadata($db_id, $table_id, $field_data);
			
			$response=array(
				'status'=>'success',
				'action' => $is_update ? 'updated' : 'created',
				'result' => $result
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
	 * Delete field metadata
	 * 
	 * DELETE /api/tables/{db_id}/{table_id}/fields/{field_name}
	 */
	function field_delete($db_id=null, $table_id=null, $field_name=null)
	{
		$this->is_admin_or_die();
		
		try{
			$user_id=$this->get_api_user_id();
			
			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');

			if(!$field_name){
				throw new Exception("MISSING_PARAM:: field_name");
			}
			
			$result = $this->Data_table_mongo_model->delete_field_metadata($db_id, $table_id, $field_name);
			
			$response=array(
				'status'=>'success',
				'result' => $result
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
	 * Delete field metadata (POST alias)
	 * 
	 * POST /api/tables/{db_id}/{table_id}/fields/{field_name}/delete
	 */
	function fields_delete_post($db_id=null, $table_id=null, $field_name=null)
	{
		// Call the same logic as field_delete
		return $this->field_delete($db_id, $table_id, $field_name);
	}

	/**
	 * Reorder fields for a table
	 * 
	 * POST /api/tables/{db_id}/{table_id}/fields/reorder
	 * Body: { "field_orders": {"field1": 1, "field2": 2, ...} }
	 */
	function fields_reorder_post($db_id=null, $table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			$options = $this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			if(!$db_id){
				throw new Exception("MISSING_PARAM:: db_id");
			}

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			if(!isset($options['field_orders']) || !is_array($options['field_orders'])){
				throw new Exception("MISSING_PARAM:: field_orders (must be an object)");
			}
			
			$result = $this->Data_table_mongo_model->reorder_fields($db_id, $table_id, $options['field_orders']);
			
			$response=array(
				'status'=>'success',
				'result' => $result
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
	 * Create indexes on table_dictionary collection
	 * This is a one-time setup operation
	 * 
	 * POST /api/tables/create_dictionary_indexes
	 * 
	 */
	function create_dictionary_indexes_post()
	{
		$this->is_admin_or_die();
		
		try{
			$user_id=$this->get_api_user_id();
			
			$results = $this->Data_table_mongo_model->create_dictionary_indexes();
			
			$response=array(
				'status'=>'success',
				'message' => 'Indexes created successfully',
				'results' => $results
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
	 * Populate table fields by reading one row from the actual data collection
	 * and storing field information in the table_dictionary collection
	 * 
	 * POST /api/tables/{db_id}/{table_id}/fields/populate
	 * 
	 * @db_id - database id
	 * @table_id - table id
	 * 
	 */
	/**
	 * Sync field metadata with actual data fields
	 * Deletes fields that don't exist in data and adds new fields from data
	 * 
	 * POST /api/tables/fields/{db_id}/{table_id}/sync
	 * 
	 * @db_id - database id
	 * @table_id - table id
	 * 
	 */
	function fields_sync_post($db_id=null, $table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			$user_id=$this->get_api_user_id();
			
			
			$db_id = $this->Data_table_mongo_model->validate_and_normalize_id($db_id, 'db_id');
			$table_id = $this->Data_table_mongo_model->validate_and_normalize_id($table_id, 'table_id');
			
			$result = $this->Data_table_mongo_model->sync_table_fields($db_id, $table_id);
			
			$response=array(
				'status'=>'success',
				'message' => 'Fields synced successfully',
				'fields_removed' => $result['fields_removed'],
				'fields_added' => $result['fields_added'],
				'total_fields' => $result['total_fields']
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

	function fields_populate_post($db_id=null,$table_id=null)
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

			// Populate schema from actual data using new method
			$fields_created = $this->Data_table_mongo_model->populate_table_schema($db_id, $table_id);
			
			$response=array(
				'status'=>'success',
				'db_id' => $db_id,
				'table_id' => $table_id,
				'total_fields' => $fields_created,
				'message' => 'Table schema populated successfully'
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

	public function _auth_override_check()
    {
        if ($this->session->userdata('user_id')){
            return TRUE;
        }
        parent::_auth_override_check();
    }

}	
