<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

use League\Csv\Reader;

class Tables extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");        
		$this->load->model("Data_table_model");
		$this->load->model("Data_tables_places_model");        
	}

	//list all tables with count
	function index_get()
	{
		try{
			$options=$this->raw_json_input();
            $user_id=$this->get_api_user_id();

            $result=$this->Data_table_model->get_tables_w_count();
			
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
	 * Get table summary
	 * 
	 */
	function info_get($table_id=null)
	{
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();			
			
			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

            $result=$this->Data_table_model->get_table_info($table_id);
			
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
	function data_get($table_id=null,$limit=null)
	{
		try{

			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}

			//$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

			if(!$table_id){
				throw new Exception("MISSING_PARAM:: table_id");
			}

			$result=$this->Data_table_model->get_table_data($table_id,$limit,$options);
			
			if(isset($options['flat_output']))
			{
				$response=$result['data'];
			}
			else{
				$response=array(
					'status'=>'success',
					'count'=>@count($result['data']),
					'result'=>$result
				);
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
	 * Create table row	 
	 * 
	 */
	function insert_post()
	{
		$this->is_admin_or_die();

		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

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

			$result=$this->Data_table_model->table_batch_insert($options);


			$response=array(
                'status'=>'success',
				"output"=>$result,
				'query'=>$this->db->last_query()
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
				'message'=>$e->getMessage(),
				'sql'=>$this->db->last_query()
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
	private function upload_file()
	{		
		if(!isset($_FILES['file'])){
			throw new Exception("FILE NOT PROVIDED");
		}

		$overwrite=$this->input->post("overwrite");

		if($overwrite=='yes'){
			$overwrite=true;
		}

		$file_name=$_FILES['file'];
		$this->load->library('upload');
	
		$config['upload_path'] = 'datafiles/tmp/';
		$config['overwrite'] = $overwrite;
		$config['encrypt_name']=false;
		$config['allowed_types'] = 'txt|csv';
		
		$this->upload->initialize($config);
		
		$upload_result=$this->upload->do_upload('file');

		if(!$upload_result){
			$error = $this->upload->display_errors();            
			throw new Exception("UPLOAD_FAILED::".$upload_path. ' - error:: '.$error);
		}

		$upload_data = $this->upload->data();			
		return $upload_data;
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
			$uploaded_file=$this->upload_file();
			$user_id=$this->get_api_user_id();

			if (!isset($uploaded_file['full_path']) ){
				throw new Exception("File not uploaded");
			}

			if (!file_exists($uploaded_file['full_path'])){
				throw new Exception("Uploaded file was not found");
			}
			
			$table_id=$this->input->post('table_id');
			$table_features=(array)$this->Data_table_model->get_features_by_table($table_id);

			//flip keys with values for looking up features by names e.g. sex instead of feature_1
			$features_flip=array();
			if(!empty($table_features)){
				$features_flip=array_flip($table_features);
			}

			$csv_path=$uploaded_file['full_path'];
			$csv=Reader::createFromPath($csv_path,'r');
			$csv->setHeaderOffset(0);

			$header=$csv->getHeader();
			$records= $csv->getRecords();

			$chunk_size =5000;
			$chunked_rows=array();
			$k=1;
			$total=0;
			$start_time=date("H:i:s");

			//delete existing table data
			$this->Data_table_model->delete_table_data($table_id);

			foreach($records as $row){

				$row['table_id']=$table_id;
				$row['dataset']=$table_id;

				//feature mapping - map e.g. sex to feature_1
				foreach($features_flip as $feature_name=>$feature_num){
					if(isset($row[$feature_name])){
						$row[$feature_num]=$row[$feature_name];
					}
				}

				$total++;
				$chunked_rows[]=$row;

				if($k>=$chunk_size){
					$result=$this->Data_table_model->table_batch_insert($chunked_rows);
					$k=1;
					$chunked_rows=array();
					set_time_limit(0);
					//break;
				}

				$k++;				
			}

			if(count($chunked_rows)>0){
				$result=$this->Data_table_model->table_batch_insert($chunked_rows);
			}
			
			$response=array(
                'status'=>'success',
				"output"=>$total,
				'start'=>$start_time,
				'end'=>date("H:i:s")
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'sql'=>$this->db->last_query()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }
	


	/**
	 * 
	 * Return geo levels
	 * 
	 * 
	 */
	function geo_levels_get()
	{
		//$this->is_admin_or_die();

		try{
            $result=$this->Data_tables_places_model->get_regions();

			$response=array(
                'status'=>'success',
				'result'=>$result,
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				//'query_options'=>$options
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	//function batch_create_regions($region_type,$data,$parent_type=NULL,$parent_uid=NULL)
	function batch_create_region_post($region_type=NULL)
	{
		//$this->is_admin_or_die();

		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

			//var_dump($options);
			//die();
			
			$params=array('data','parent_type','parent_uid');

			foreach($params as $param){
				if(!array_key_exists($param,$options)){
					$options[$param]=null;
				}
			}

			
            $result=$this->Data_tables_places_model->batch_create_regions(
				$region_type,
				$options['data'],
				$options['parent_type'],
				$options['parent_uid']
			);			

			$response=array(
                'status'=>'success',
				'result'=>$options,
				//'query'=>$this->db->last_query()
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
				'message'=>$e->getMessage(),
				'query_options'=>$options
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * 
	 * Create regions
	 * 
	 * State
	 * region_type=state, options(uid,name)
	 * region_type=district, options(uid,name,state=uid|mame)
	 * region_type=subdistrict, options(uid,name,district=uid|mame)
	 * region_type=town, options(uid,name,district=uid|mame)
	 * 
	 */
	function create_region_post($region_type=NULL)
	{
		//$this->is_admin_or_die();

		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();

			//var_dump($options);
			//die();
			
			$params=array('uid','name','parent_type','parent_uid');

			foreach($params as $param){
				if(!array_key_exists($param,$options)){
					$options[$param]=null;
				}
			}

			

            $result=$this->Data_tables_places_model->create_region(
				$region_type,
				$options['uid'],
				$options['name'],
				$options['parent_type'],
				$options['parent_uid']
			);			

			$response=array(
                'status'=>'success',
				'result'=>$options,
				//'query'=>$this->db->last_query()
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
				'message'=>$e->getMessage(),
				'query_options'=>$options
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * get regions
	 * 
	 * region_type=state, district, etc
	 * 
	 */
	function regions_get($region_type=NULL,$region_uid=NULL)
	{
		//$this->is_admin_or_die();

		try{
			$options=$this->raw_json_input();
			
            $result=$this->Data_tables_places_model->find_regions($region_type,$region_uid);			

			$response=array(
                'status'=>'success',
				'result'=>$result,
				//'query'=>$this->db->last_query()
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
				'message'=>$e->getMessage(),
				//'query_options'=>$options
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}




    
	function create_table_post()
	{
		$this->is_admin_or_die();

		try{
			$options=$this->raw_json_input();
            $user_id=$this->get_api_user_id();			

            $result=$this->Data_table_model->create_table(                
                $options
			);
			

			$response=array(
                'status'=>'success',
				'result'=>$result,
				'query'=>$this->Data_table_model->get_db_error()				
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


	function delete_delete($table_id=null)
	{
		$this->is_admin_or_die();
		
		try{
			$options=$this->raw_json_input();
            $user_id=$this->get_api_user_id();			

            $result=$this->Census_table_model->delete_table_data($table_id);
			

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

	




	function states_get()
	{
		try{
			$options=$this->raw_json_input();
            $user_id=$this->get_api_user_id();			

            $result=$this->Census_regions_model->get_states();
			
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


	function districts_get($state=null)
	{
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();			
			
			if(!$state){
				throw new Exception("State Name or Code is required");
			}

            $result=$this->Census_regions_model->get_districts($state);
			
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

	function subdistricts_get($district_code=null)
	{
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();			
			
			if(!$district_code){
				throw new Exception("District code is required");
			}

            $result=$this->Census_regions_model->get_subdistricts($district_code);
			
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

	function towns_get($district_code=null)
	{
		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();			
			
			if(!$district_code){
				throw new Exception("District code is required");
			}

            $result=$this->Census_regions_model->get_towns($district_code);
			
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
	function population_by_age_get()
	{
		try{
			//$options=$this->raw_json_input();

			$get_params=array();
			parse_str($_SERVER['QUERY_STRING'], $get_params);
			
			$options=array();
			foreach(array_keys($get_params) as $param){
				$options[$param]=$this->input->get($param,true);
			}


			$this->load->model("Census_table_utils_model");
			$result=$this->Census_table_utils_model->population_by_age($options);
			
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


}	
