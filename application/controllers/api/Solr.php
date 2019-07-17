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
	 * recursive function to import all surveys
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function full_import_surveys_get($start_row=NULL, $limit=10)
	{		        
        try{
			$output=$this->solr_manager->full_import_surveys($start_row, $limit, $loop=false);
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
	 * recursive function to import all variables
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function full_import_variables_get($start_row=0, $limit=100)
	{
        try{
			$output=$this->solr_manager->full_import_variables($start_row, $limit, $loop=false);
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
	 * recursive function to import all variables
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function full_import_citations_get($start_row=0, $limit=5000)
	{
        try{
			$output=$this->solr_manager->full_import_citations($start_row, $limit, $loop=false);
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
            $output=$this->solr_manager->get_db_counts();
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
            $output=$this->solr_manager->clean_index();
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
            $output=$this->solr_manager->commit();
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


}