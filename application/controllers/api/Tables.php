<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Tables extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct(); 
		$this->load->helper("date");
		$this->load->model("Data_table_mongo_model");
		$this->load->model("Data_table_model");
	}

	//list all tables with count
	function index_get($db_id=null)
	{
		try{
			//$options=$this->raw_json_input();
			//$user_id=$this->get_api_user_id(); 
			
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

			$result=array(
				'storageUnit'=>'M',
				'size'=>$result['size'],
				'count'=>$result['count'],
				'storageSize'=>$result['storageSize'],
				'nindexes'=>$result['nindexes'],
				//'indexes'=>$result['indexDetails'],
				'indexNames'=>array_keys((array)$result['indexDetails']),
				'result_'=>$result['table_type']
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
	 * Create index for a collection 
	 * 
	 */
	function indexes_delete($db_id=null,$table_id=null,$index_name=null)
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
	 * Filters
	 * - table id
	 * - region_type
	 * - state_code
	 * - district_code
	 * - subdistrict_code
	 * - village_town_code
	 * - ward_code
	 * 
	 * - features
	 * 
	 * 
	 * 
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

			$labels=explode(",",$this->input->get("labels"));

			$response=$this->Data_table_mongo_model->get_table_data($db_id,$table_id,$limit,$offset,$options,$labels);
			
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
	 * Create table row	 
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
			
            //validate all rows
            /*
			foreach($options as $key=>$row){
				$this->Census_table_model->validate_table($row);
            }
            */

			/*$result=array();
            foreach($options as $row)
            {               
				$result=$this->Census_table_model->insert_table($row['table_id'],$row);
			}*/

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
	

	function upload_post($db_id,$table_id)
	{
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

			if($this->is_zip_file($uploaded_file_path)){
				$unzip_path='datafiles/'.$db_id.'/'.$table_id;
				$uploaded_file_path=$this->get_file_from_zip($uploaded_file_path,$unzip_path);
			}

			//drop existing table
			$this->Data_table_mongo_model->delete_table_data($db_id,$table_id);

			//import csv
			$result=$this->Data_table_mongo_model->import_csv($db_id,$table_id,$uploaded_file_path, $this->input->post("delimiter"));

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

	function file_ext_get()
	{
		try{			

			$file=$this->input->get("file");

			$response=array(
                'status'=>'success',
				'file_path'=>$this->is_zip_file($file),				
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


	/**
	 * 
	 * 
	 * Create table row	 
	 * 
	 */
	function import_post()
	{
		$this->is_admin_or_die();

		try{
			//$uploaded_file=$this->upload_file();
			$user_id=$this->get_api_user_id();
			$options=$this->raw_json_input();

			if (!isset($options['file_path']) ){
				throw new Exception("File_path not provided");
			}

			if (!file_exists('datafiles/'.$options['file_path'])){
				throw new Exception("file_path was not found");
			}
			
			$db_id=$options['db_id'];
			$table_id=$options['table_id'];


			if (!$db_id){
				throw new exception("Missing Param:: dbId");
			}

			if (!$table_id){
				throw new exception("Missing Param:: tableId");
			}
			
			/*$table_features=(array)$this->Data_table_model->get_features_by_table($table_id);

			//flip keys with values for looking up features by names e.g. sex instead of feature_1
			$features_flip=array();
			if(!empty($table_features)){
				$features_flip=array_flip($table_features);
			}
			*/
			
			$start_time=date("H:i:s");

			//drop existing table
			$this->Data_table_mongo_model->delete_table_data($db_id,$table_id);

			//import csv
			$result=$this->Data_table_mongo_model->import_csv($db_id,$table_id,'datafiles/'.$options['file_path']);
			
			$response=array(
                'status'=>'success',
				"result"=>$result,
				'start'=>$start_time,
				'end'=>date("H:i:s")
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

            $result=$this->Data_table_mongo_model->delete_table_data($db_id,$table_id);
			

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
	 * Get population by age table
	 * 
	 */
	function population_by_age_get($db_id,$table_id)
	{
		try{
			//$options=$this->raw_json_input();

			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}

			$result=$this->Data_table_mongo_model->population_by_age($db_id,$table_id,$options);
			
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
	 * Search for States, districts, towns, etc
	 * 
	 * geo_level - state, district, etc
	 * state - state name
	 * district - district name
	 * subdistrict
	 * town
	 * village
	 * 
	 */
	function search_place_get()
	{
		try{
			//$options=$this->raw_json_input();

			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}

			$this->load->model("Data_tables_places_model");
			$result=$this->Data_tables_places_model->search($options);
			
			$response=array(
				'status'=>'success',				
				'result'=>$result,
				'query'=>$this->db->last_query()
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
	 * Search for States, districts, towns, etc
	 * 
	 * geo_level - state, district, etc
	 * state - state code
	 * district - district code
	 * subdistrict
	 * town
	 * village
	 * areaname - text search
	 * 
	 * {$text:{$search:"Haniya"}, level:"state"}
	 * 
	 */
	function geosearch_get($db_id=null)
	{
		try{
			//$options=$this->raw_json_input();

			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}

			$fields=$this->input->get("fields");

			$result=$this->Data_table_mongo_model->geo_search($db_id,$options,$fields);

			if (strtolower($this->input->get("codelists"))==='true'){
				$result['codelist']=$this->geosearch_codelists($db_id,$result['data']);
			}
			
			$this->set_response($result, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	private function geosearch_codelists($db_id,$places)
	{
		$places_map=array(            
            'state'=> 'state',
            'district'=> 'district',
            'subdistrict'=> 'subdistrict',
			'town'=>'town_village',
			'village'=>'town_village',
            'ward'=> 'ward',
        );

		$codelist=array();

		foreach($places as $place){
			
			$codelist['state'][]=$place['state'];
			$codelist['district'][]=$place['district'];
			$codelist['subdistrict'][]=$place['subdistrict'];
			
			//$codelist['town_village'][]=$place['town_village'];
			$codelist['ward'][]=$place['ward'];
			
			if ($place['level']=='town'){
				$codelist['town'][]=$place['town_village'];
			}else if ($place['level']=='village'){
				$codelist['village'][]=$place['town_village'];
			}
		}

		//return $codelist;

		$output=array();

		foreach($codelist as $place_type=>$values){
			
			$values=array_filter(array_unique($values));

			if (empty($values) && count($values)<1 ){
				continue;
			}

			/*echo $place_type;
			var_dump($values);
			continue;*/

			$options=array(
				'level'=>$place_type
			);

			$options[$places_map[$place_type]]=implode(",",$values);
						
			$result=$this->Data_table_mongo_model->geo_search(
				$db_id,
				$options,
				$fields=implode(",",array('areaname','level', $places_map[$place_type])) );
			
			if ($this->input->get("debug")){
				$output['query'][]=$result;
			}

			if (isset($result['data'])){
				foreach($result['data'] as $code_row){
					$output[$code_row['level']][$code_row[$places_map[$place_type]]]=$code_row['areaname'];
				}
			}
		}

		return $output;
	}
}	
