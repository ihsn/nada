<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Facets extends MY_REST_Controller
{
	public function __construct()
	{
        parent::__construct();
        $this->load->model("Facet_model");
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


    


    /**
	 *
	 * recursive function to reindex all facets
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function reindex_get($start_row=NULL, $limit=10)
	{		        
        try{
			$output=$this->Facet_model->reindex($start_row, $limit, $loop=false);
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
            $output='TODO';
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