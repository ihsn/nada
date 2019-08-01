<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Data extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");        
		$this->load->model("Census_table_model");
		$this->load->model("Data_tables_places_model");        
	}

	//list all tables with count
	function index_get()
	{
		try{
			$options=$this->raw_json_input();
            $user_id=$this->get_api_user_id();			

            $result=$this->Census_table_model->get_tables_w_count();
			
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

            $result=$this->Census_table_model->get_table_info($table_id);
			
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

			$result=$this->Census_table_model->get_table_data($table_id,$limit,$options);
			
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
				'message'=>$e->getMessage()
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

			$result=$this->Census_table_model->table_batch_insert($options);


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




    /**
	 * 
	 * 
	 * Create regions
	 * 
	 */
	function old_create_region_post()
	{
		$this->is_admin_or_die();

		try{
			$options=$this->raw_json_input();
            $user_id=$this->get_api_user_id();			

            $result=$this->Census_regions_model->create_region(                
                $options['region_type'],
				$options['uid'],
				$options['name'],
                $options['state_code'],
                $options['district_code'],
                $options['subdistrict_code'],
                $options['town_code']
			);
			

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


	function create_table_post()
	{
		$this->is_admin_or_die();

		try{
			$options=$this->raw_json_input();
            $user_id=$this->get_api_user_id();			

            $result=$this->Census_table_model->create_table(                
                $options
			);
			

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
