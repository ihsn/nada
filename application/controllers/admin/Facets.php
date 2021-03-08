<?php
class Facets extends MY_Controller {
 
    function __construct() 
    {
        parent::__construct();   
		$this->load->model('Facet_model');
		$this->load->model('Dataset_model');
		$this->template->set_template('admin5');
    	
		$this->lang->load('general');
		$this->acl_manager->has_access_or_die('facets', 'edit');
		//$this->output->enable_profiler(TRUE);	
	}
 
 
	function index()
	{
		$data['rows']=$this->Facet_model->select_terms_counts();		
		$content=$this->load->view('facets/index', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Facets'),true);
	  	$this->template->render();
	}


	function terms($facet_id=null)
	{
		$facet=$this->Facet_model->select_single($facet_id);
		
		if(empty($facet)){
			show_error("FACET not found");
		}
		
		$data['facet']=$facet;
		$data['rows']=$this->Facet_model->get_facet_terms($facet_id);
		
		$content=$this->load->view('facets/terms', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Facets'),true);
	  	$this->template->render();
	}


	function indexer()
	{
		$data['rows']=$this->Facet_model->select_term_value_counts();
		$data['studies_count']=$this->Dataset_model->get_total_count();
		$content=$this->load->view('facets/indexer', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Facets'),true);
	  	$this->template->render();
	}



	/**
	* Delete one or more records
	* note: to use with ajax/json, pass the ajax as querystring
	* 
	* id 	int or comma seperate string
	*/
	function delete($id)
	{
		$this->acl_manager->has_access_or_die('facet', 'delete');

		//array of id to be deleted
		$delete_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($id))
		{
			$tmp_arr=explode(",",$id);
		
			foreach($tmp_arr as $key=>$value)
			{
				if (is_numeric($value))
				{
					$delete_arr[]=$value;
				}
			}
			
			if (count($delete_arr)==0)
			{
				//for ajax return JSON output
				if ($ajax!='')
				{
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
				}
				
				$this->session->set_flashdata('error', 'Invalid id was provided.');
				redirect('admin/menu',"refresh");
			}	
		}		
		else
		{
			$delete_arr[]=$id;
		}
		
		if ($this->input->post('cancel')!='')
		{
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/facets');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->Facet_model->delete_facet($item);
			}

			//for ajax calls, return output as JSON						
			if ($ajax!='')
			{
				echo json_encode(array('success'=>"true") );
				exit;
			}
						
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/facets');
			}	
		}
		else
		{
			//ask for confirmation
			$content=$this->load->view('resources/delete', NULL,true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}
	
	
	
	
	
	
}    