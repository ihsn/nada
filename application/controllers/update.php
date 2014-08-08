<?php
/**
*
* database migrations
*
**/
class Update extends MY_Controller {
 
    function __construct() 
    {
        parent::__construct($skip_auth=TRUE);		
    	$this->load->library('migration');
    }
  
    function index()
    {
	//echo 'hi';
    }
    
    function version($version)
    {
	$migration=$this->migration->version($version);
	if (!$migration)
	{
	    echo $this->migration->error_string();
	}
	else
	{
	    echo 'migration successful!';
	}
	
    }


}//end class