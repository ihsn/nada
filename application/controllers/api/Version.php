<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Version extends MY_REST_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);        
    }

    
    function index_get()
    {
        try{
			// get APP_VERSION global variable
            $output=array(
                'version'	=> APP_VERSION
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