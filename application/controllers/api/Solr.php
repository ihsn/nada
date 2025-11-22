<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Solr extends MY_REST_Controller
{
	public function __construct()
	{
        parent::__construct();
        $this->load->library("Solr_manager");
		$this->is_admin_or_die();
    }
    
    //override authentication to support both session authentication + api keys
    function _auth_override_check()
    {
        //session user id
        if ($this->session->userdata('user_id'))
        {
            //var_dump($this->session->userdata('user_id'));
            return true;
        }

        parent::_auth_override_check();
    }


    function ping_get()
    {
        try{
			$output=$this->solr_manager->ping_test();
			$output=array(
                'status'=>'success',
                'result'=>$output
			);
			$this->set_response($output, REST_Controller::HTTP_OK);			
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
	 * Import surveys in batches
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 *
	 * */
	public function import_surveys_batch_get($start_row=NULL, $limit=10)
	{		        
        try{
			$output=$this->solr_manager->import_surveys_batch($start_row, $limit, $loop=false);
			$output=array(
                'status'=>'success',
                'result'=>$output
			);
			$this->set_response($output, REST_Controller::HTTP_OK);			
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
	 * Import variables in batches
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 *
	 * */
	public function import_variables_batch_get($start_row=0, $limit=100)
	{
        $debug = $this->get('debug') === 'true' || $this->get('debug') === true;
        
        if ($debug) {
            $this->db->save_queries = TRUE;
        }
        
        try{
			$output=$this->solr_manager->import_variables_batch($start_row, $limit, $loop=false);
			$response=array(
                'status'=>'success',
                'result'=>$output
			);
			
			if ($debug) {
				$profiler_data = array(
					'query_count' => $this->db->query_count,
					'queries' => array(),
					'total_time' => 0
				);
				
				if (isset($this->db->queries) && is_array($this->db->queries)) {
					$total_time = 0;
					foreach ($this->db->queries as $key => $query) {
						$time = isset($this->db->query_times[$key]) ? $this->db->query_times[$key] : 0;
						$total_time += $time;
						$profiler_data['queries'][] = array(
							'query' => $query,
							'time' => $time
						);
					}
					$profiler_data['total_time'] = $total_time;
				}
				
				$response['debug'] = $profiler_data;
			}
			
			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
            $error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			
			if ($debug) {
				$profiler_data = array(
					'query_count' => isset($this->db->query_count) ? $this->db->query_count : 0,
					'queries' => array(),
					'total_time' => 0
				);
				
				if (isset($this->db->queries) && is_array($this->db->queries)) {
					$total_time = 0;
					foreach ($this->db->queries as $key => $query) {
						$time = isset($this->db->query_times[$key]) ? $this->db->query_times[$key] : 0;
						$total_time += $time;
						$profiler_data['queries'][] = array(
							'query' => $query,
							'time' => $time
						);
					}
					$profiler_data['total_time'] = $total_time;
				}
				
				$error_output['debug'] = $profiler_data;
			}
			
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);			
		}
		
    }


    /**
     * Import all variables for a single survey with survey metadata loaded once
     * @param int $survey_id Survey ID
     * @param int $start_row Starting variable row number (default: 0)
     * @param int $limit Number of variables per batch (default: 200)
     */
    public function import_variables_by_survey_batch_get($survey_id, $start_row=0, $limit=200)
    {
        try{            
            $auto_process_all = ($start_row == 0 && $limit == 200);
            $loop = $auto_process_all ? true : false;
            
            log_message('info', 'Starting variable import for survey ' . $survey_id . ' - Auto process all: ' . ($auto_process_all ? 'Yes' : 'No'));
            
            $output = $this->solr_manager->import_variables_by_survey_batch($survey_id, $start_row, $limit, $loop);
            $output = array(
                'status' => 'success',
                'result' => $output,
                'auto_process_all' => $auto_process_all
            );
            $this->set_response($output, REST_Controller::HTTP_OK);			
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);			
        }
    }


    /**
	 *
	 * Import citations in batches
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 *
	 * */
	public function import_citations_batch_get($start_row=0, $limit=5000)
	{
        try{
			$output=$this->solr_manager->import_citations_batch($start_row, $limit, $loop=false);
			$output=array(
                'status'=>'success',
                'result'=>$output
			);
			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
            $error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);			
		}
		
    }
    

    public function db_counts_get()
    {	 
        try{
            $output=$this->solr_manager->count_database_records();
            $output=array(
                'status'=>'success',
                'result'=>$output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);			
        }
        catch(Exception $e){
            $error_output=array(
                'status'=>'failed',
                'message'=>$e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);			
        }
    }

    public function index_counts_get()
    {
        try{
            $output=$this->solr_manager->count_solr_records();
            
            try {
                $output['last_dataset']=$this->solr_manager->get_last_document_id(1);
            } catch (Exception $e) {
                $output['last_dataset'] = null;
            }
            
            try {
                $output['last_variable']=$this->solr_manager->get_last_document_id(2);
            } catch (Exception $e) {
                $output['last_variable'] = null;
            }
            
            try {
                $output['last_citation']=$this->solr_manager->get_last_document_id(3);
            } catch (Exception $e) {
                $output['last_citation'] = null;
            }

            $output=array(
                'status'=>'success',
                'result'=>$output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);			
        }
        catch(Exception $e){
            $error_output=array(
                'status'=>'failed',
                'message'=>$e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);			
        }
    }

    public function index_last_document_id_get()
    {
        try{
            $output=$this->solr_manager->get_last_document_id();
            $output=array(
                'status'=>'success',
                'result'=>$output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);			
        }
        catch(Exception $e){
            $error_output=array(
                'status'=>'failed',
                'message'=>$e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);			
        }
    }


    public function clear_index_get()
    {        
        try{
            $output=$this->solr_manager->clear_index();
            $output=array(
                'status'=>'success',
                'result'=>$output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);			
        }
        catch(Exception $e){
            $error_output=array(
                'status'=>'failed',
                'message'=>$e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);			
        }
    }

    public function commit_get()
    {        
        try{
            $output=$this->solr_manager->commit_index_changes();
            $output=array(
                'status'=>'success',
                'result'=>$output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);			
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
     * Schema Management Methods
     */
    
    public function schema_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $output = $schema_manager->get_schema();
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_fields_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $output = $schema_manager->get_fields();
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_setup_variables_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            // Check if replace parameter is provided
            $replace_existing = $this->get('replace') === 'true' || $this->get('replace') === '1';
            
            $output = $schema_manager->setup_variable_fields($replace_existing);
            $output = array(
                'status' => 'success',
                'result' => $output,
                'replace_existing' => $replace_existing
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_setup_surveys_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            //check if replace parameter is provided
            $replace_existing = $this->get('replace') === 'true' || $this->get('replace') === '1';
            
            //survey fields for embedding in variable documents (denormalized)
            $output = $schema_manager->setup_survey_fields($replace_existing);
            $output = array(
                'status' => 'success',
                'result' => $output,
                'replace_existing' => $replace_existing,
                'note' => 'This sets up survey fields for embedding in variable documents (denormalized model)'
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_setup_survey_documents_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            //check if replace parameter is provided
            $replace_existing = $this->get('replace') === 'true' || $this->get('replace') === '1';
            
            //comprehensive schema for actual survey documents (doctype=1)
            $output = $schema_manager->setup_survey_document_fields($replace_existing);
            $output = array(
                'status' => 'success',
                'result' => $output,
                'replace_existing' => $replace_existing,
                'note' => 'This sets up comprehensive schema for actual survey documents (doctype=1)'
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_setup_complete_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $replace_existing = $this->get('replace') === 'true' || $this->get('replace') === '1';
            
            $output = $schema_manager->setup_complete_schema($replace_existing);
            $output = array(
                'status' => 'success',
                'result' => $output,
                'replace_existing' => $replace_existing
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_validate_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $output = $schema_manager->validate_schema();
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_setup_status_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $output = $schema_manager->check_schema_setup_status();
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage(),
                'result' => array(
                    'setup_complete' => false,
                    'can_index' => false,
                    'status' => 'error'
                )
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_stats_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $output = $schema_manager->get_schema_stats();
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_debug_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $config_check = array(
                'solr_schema_fields' => $this->config->item('solr_schema_fields', 'solr'),
                'all_solr_config' => $this->config->item('solr'),
                'config_file_loaded' => $this->config->is_loaded('solr')
            );
            
            $output = array(
                'config_check' => $config_check,
                'configured_variable_fields' => $schema_manager->get_configured_variable_fields(),
                'configured_survey_fields' => $schema_manager->get_configured_survey_fields(),
                'all_configured_fields' => $schema_manager->get_all_configured_fields()
            );
            
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_connection_test_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $output = $schema_manager->test_connection();
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_test_command_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $output = $schema_manager->test_schema_command();
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_field_usage_get($field_name)
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $output = $schema_manager->get_field_usage($field_name);
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_copy_fields_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $output = $schema_manager->get_copy_fields();
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_delete_copy_field_post()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $source = $this->post('source');
            $dest = $this->post('dest');
            
            if (empty($source) || empty($dest)) {
                $this->set_response(array(
                    'status' => 'failed',
                    'message' => 'source and dest are required'
                ), REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
            
            $output = $schema_manager->delete_copy_field($source, $dest);
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_field_types_get()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $output = $schema_manager->get_available_field_types();
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_replace_field_post()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $field_name = $this->post('field_name');
            $new_definition = $this->post('new_definition');
            
            if (empty($field_name) || empty($new_definition)) {
                $this->set_response(array(
                    'status' => 'failed',
                    'message' => 'field_name and new_definition are required'
                ), REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
            
            $output = $schema_manager->replace_field($field_name, $new_definition);
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_update_field_properties_post()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $field_name = $this->post('field_name');
            $properties = $this->post('properties');
            
            if (empty($field_name) || empty($properties)) {
                $this->set_response(array(
                    'status' => 'failed',
                    'message' => 'field_name and properties are required'
                ), REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
            
            $output = $schema_manager->update_field_properties($field_name, $properties);
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    public function schema_copy_field_post()
    {
        try{
            $this->load->library('Solr_schema_manager');
            $schema_manager = new Solr_schema_manager();
            
            $source_field = $this->post('source_field');
            $dest_field = $this->post('dest_field');
            
            if (empty($source_field) || empty($dest_field)) {
                $this->set_response(array(
                    'status' => 'failed',
                    'message' => 'source_field and dest_field are required'
                ), REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
            
            $output = $schema_manager->copy_field($source_field, $dest_field);
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Survey metadata configuration methods
     */
    
    public function survey_metadata_config_get()
    {
        try{
            $output = array(
                'solr_variable_include_survey_metadata' => $this->config->item('solr_variable_include_survey_metadata'),
                'solr_survey_metadata_fields' => $this->config->item('solr_survey_metadata_fields')
            );
            
            $output = array(
                'status' => 'success',
                'result' => $output
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get all survey IDs from database
     */
    public function get_all_survey_ids_get()
    {
        try{
            $this->load->model('catalog_model');
            $survey_ids = $this->catalog_model->get_all_survey_ids();
            
            $output = array(
                'status' => 'success',
                'result' => array(
                    'survey_ids' => $survey_ids
                )
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Core Management Methods
     */
    
    /**
     * Create a new Solr core
     * GET /api/solr/core_create/{core_name}?instance_dir={dir}&config_set={set}
     */
    public function core_create_get($core_name)
    {
        try{
            $instance_dir = $this->get('instance_dir') ?: $core_name;
            // Allow empty config_set - if not provided or empty, don't set it (let Solr use default)
            $config_set = $this->get('config_set');
            if (empty($config_set)) {
                $config_set = null; // Don't set configSet parameter
            }
            
            $result = $this->solr_manager->create_core($core_name, $instance_dir, $config_set);
            
            $output = array(
                'status' => 'success',
                'result' => $result
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * List all Solr cores
     * GET /api/solr/core_list
     */
    public function core_list_get()
    {
        try{
            $cores = $this->solr_manager->list_cores();
            
            $output = array(
                'status' => 'success',
                'result' => $cores
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * Get status of a specific core
     * GET /api/solr/core_status/{core_name}
     */
    public function core_status_get($core_name)
    {
        try{
            $status = $this->solr_manager->get_core_status($core_name);
            
            if ($status === null) {
                $output = array(
                    'status' => 'failed',
                    'message' => "Core '$core_name' not found"
                );
                $this->set_response($output, REST_Controller::HTTP_NOT_FOUND);
            } else {
                $output = array(
                    'status' => 'success',
                    'result' => $status
                );
                $this->set_response($output, REST_Controller::HTTP_OK);
            }
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * Check if configured core exists and get its status
     * GET /api/solr/core_check_configured
     */
    public function core_check_configured_get()
    {
        try{
            //get configured core name from Solr_manager
            $configured_core = $this->solr_manager->get_configured_core_name();            
            $configured_core = trim($configured_core);
                        
            if (empty($configured_core)) {
                $output = array(
                    'status' => 'error',
                    'message' => 'No core configured in solr.php config file. Please set $config[\'solr_collection\']',
                    'configured_core' => null,
                    'exists' => false
                );
                $this->set_response($output, REST_Controller::HTTP_OK);
                return;
            }
            
            $status = null;
            $exists = false;
            $status_error = null;
            
            try {
                $status = $this->solr_manager->get_core_status($configured_core);
                $exists = ($status !== null && $status !== false && (is_array($status) ? !empty($status) : true));
            } catch (Exception $e) {
                log_message('debug', 'Core status check exception (core may not exist): ' . $e->getMessage());
                $status = null;
                $exists = false;
                $status_error = $e->getMessage();
            }
            
            $output = array(
                'status' => 'success',
                'configured_core' => $configured_core,
                'exists' => $exists,
                'core_status' => $status,
                'status_error' => $status_error
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            log_message('error', 'Error checking configured core: ' . $e->getMessage());
            log_message('error', 'Error stack trace: ' . $e->getTraceAsString());
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * Get Solr system information including version
     * GET /api/solr/system_info
     */
    public function system_info_get()
    {
        try{
            $system_info = $this->solr_manager->get_solr_system_info();
            
            $output = array(
                'status' => 'success',
                'result' => $system_info
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * Delete/unload a Solr core
     * GET /api/solr/core_delete/{core_name}?delete_index=true&delete_data_dir=true&delete_instance_dir=true
     */
    public function core_delete_get($core_name)
    {
        try{
            $delete_index = $this->get('delete_index') !== 'false';
            $delete_data_dir = $this->get('delete_data_dir') !== 'false';
            $delete_instance_dir = $this->get('delete_instance_dir') !== 'false';
            
            $result = $this->solr_manager->delete_core($core_name, $delete_index, $delete_data_dir, $delete_instance_dir);
            
            $output = array(
                'status' => 'success',
                'result' => $result
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * Reload a Solr core
     * GET /api/solr/core_reload/{core_name}
     */
    public function core_reload_get($core_name)
    {
        try{
            $result = $this->solr_manager->reload_core($core_name);
            
            $output = array(
                'status' => 'success',
                'result' => $result
            );
            $this->set_response($output, REST_Controller::HTTP_OK);
        }
        catch(Exception $e){
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

}