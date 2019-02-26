<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Filestore extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
        $this->load->helper("date");
        $this->load->model('Filestore_model');
		$this->is_admin_or_die();
    }
    

    /**
	 * 
	 * list files
	 * 
	 **/
	function index_get($file_name=null)
	{
		try{
			$files_count=$this->Filestore_model->get_file_counts();
			$response=array(
				'total'=>$files_count
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}	
    }
    

    /**
	 * 
	 * 
	 * Download a file
	 * 
	 */
	function download_get($filename)
	{
		try{
			return $this->Filestore_model->download($filename);
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }
    


    /**
	 * 
	 * upload file	 
	 * 
	 **/ 
	function index_post()
	{		
		try{

			if(!isset($_FILES['file'])){
                throw new Exception("FILE NOT PROVIDED");
            }

            $overwrite=$this->input->post("overwrite");

            if($overwrite=='yes'){
                $overwrite=true;
            }
    
            $result=$this->Filestore_model->upload('file',$overwrite);

			$output=array(
				'status'=>'success',
				'result'=>$result
			);

			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }
    


    /**
	 * 
	 * delete a file
	 * 
	 **/ 
	function delete_delete($filename=null)
	{
		
		try{
			$this->Filestore_model->delete($filename);

			$output=array(
				'status'=>'success'
			);

			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}



}	
