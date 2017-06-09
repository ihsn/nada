<?php
class Regions extends MY_Controller {
 
    function __construct() 
    {
        parent::__construct();   
		$this->load->model('country_region_model');
		$this->template->set_template('admin');
    	
		$this->lang->load('general');
		//$this->lang->load('country');	
	}
 
 
	function index()
	{
		$data['tree']=$this->country_region_model->get_tree();
		$content=$this->load->view('regions/index', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Regions'),true);
	  	$this->template->render();
	}
	
	
	function add()
	{
		$this->edit();
	}


	/**
	* Edit
	*
	* handles both add or edit
	*/
	function edit($id=NULL)	
	{
		if (!is_numeric($id)  && $id!=NULL)
		{
			show_error('INVALID-ID');
		}
		
		//validation rules
		$this->form_validation->set_rules('title', t('title'), 'xss_clean|trim|required|max_length[100]');
		$this->form_validation->set_rules('weight', t('weight'), 'is_numeric|xss_clean|trim|required|max_length[3]');
		
		$data=array();
		$region_parents=$this->country_region_model->get_parents();
		$data['parent_regions'][0]='--PARENT--';
		$data['country_list']=$this->country_region_model->get_countries_compact();
		
		foreach($region_parents as $item)
		{
			$data['parent_regions'][$item['id']]=$item['title'];
		}		
				
		//process form				
		if ($this->form_validation->run() == TRUE)
		{
			$options=array();
			$post_arr=$_POST;
						
			//read post values to pass to db
			foreach($post_arr as $key=>$value)
			{
				$options[$key]=$this->input->post($key);
			}					
			
			if ($id==NULL)
			{
				$db_result=$this->country_region_model->insert($options);
			}
			else
			{
				$db_result=$this->country_region_model->update($id,$options);
			}
									
			if ($db_result===TRUE)
			{
				$this->session->set_flashdata('message', t('form_update_success'));
				redirect("admin/regions","refresh");
			}
			else
			{
				//update failed
				$this->form_validation->set_error(t('form_update_fail'));
			}
			
		}
		else //loading form the first time
		{
				if ( is_numeric($id) )
				{
					//from db
					$data['row']=$this->country_region_model->select_single($id);
					
					if (!$data['row'])
					{
						show_error("INVALID-ID");
					}
				
				}
		}

		//show form
		$content=$this->load->view('regions/edit',$data,true);									
		$this->template->write('content', $content,true);
	  	$this->template->render();								
	}
	
	function delete($id)
	{
		if(!is_numeric($id))
		{
			show_error("INVALID_PARAM");
		}
		
		$this->country_region_model->delete($id);
		redirect("admin/regions");
	}


	
	

}    