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
	 * list files with search, pagination, filtering, and sorting
	 * 
	 **/
	function index_get($file_name=null)
	{
		try{

			if($file_name){
				return $this->single_file($file_name);
			}

			// Get query parameters
			$per_page = (int)$this->input->get('limit') ?: 15;
			$offset = (int)$this->input->get('offset') ?: 0;
			$sort_by = $this->input->get('sort_by') ?: 'changed';
			$sort_order = $this->input->get('sort_order') ?: 'desc';
			
			// Build filter array
			$filter = array();
			if ($this->input->get('search')) {
				$filter['keywords'] = $this->input->get('search');
				$filter['field'] = $this->input->get('field') ?: 'file_name';
			}
			if ($this->input->get('filter_type')) {
				$filter['filter_type'] = $this->input->get('filter_type');
			}
			if ($this->input->get('filter_images') === 'true' || $this->input->get('filter_images') === '1') {
				$filter['filter_images'] = true;
			}

			// Get files with search/pagination
			$files = $this->Filestore_model->search($per_page, $offset, $filter, $sort_by, $sort_order);
			$total = $this->Filestore_model->search_count($filter);

			$response=array(
				'status' => 'success',
				'total'=>$total,
				'files'=>$files
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
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
	 * Check a file exists
	 * 
	 **/
	private function single_file($file_name=null)
	{
		try{
			$file=$this->Filestore_model->get_file_with_size($file_name);

			if (!$file){
				throw new Exception("file_not_found");
			}

			$response=array(
				'status' => 'success',
				'file'=>$file				
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
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


	public function _auth_override_check()
    {
        if ($this->session->userdata('user_id')){
            return true;
        }
        return parent::_auth_override_check();
    }



}	
