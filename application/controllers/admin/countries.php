<?php
class Countries extends MY_Controller {
 
    function __construct() 
    {
        parent::__construct();   
		$this->load->model('country_model');
		$this->template->set_template('admin');
    	
		$this->lang->load('general');
		//$this->lang->load('country');	
		//$this->output->enable_profiler(TRUE);	
	}
 
 
	function index()
	{
		$data['rows']=$this->country_model->select_all();
		$data['aliases']=$this->country_model->get_country_aliases();
		
		$content=$this->load->view('countries/index', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('countries'),true);
	  	$this->template->render();
	}

	/**
	*
	* Fix study/country mappings that dont use an ISO CODE
	**/
	function mappings()
	{
		$data['rows']=$this->country_model->get_broken_study_countries();		
		$countries=$this->country_model->select_all_compact();
		$data['country_list'][0]=t('--SELECT--');
		foreach($countries as $country)
		{
			$data['country_list'][$country['countryid']]=$country['name'];
		}	
		$content=$this->load->view('countries/mappings', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('countries'),true);
	  	$this->template->render();
	}

	function fix_mappings()
	{	//?name=Africa&cid=3&Submit=Update
		$name=$this->input->get("name");
		$cid=$this->input->get("cid");
	
		if(!is_numeric($cid))
		{
			show_error("INVALID");
		}
		
		//1: create a new country alias
		$this->country_model->add_alias($cid,$name);
		
		//2: update survey_countries table and update all instances for the country name to use CID
		$this->country_model->update_survey_country_code($name,$cid);
		
		redirect("admin/countries/mappings");
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
		$this->form_validation->set_rules('name', t('name'), 'xss_clean|trim|required|max_length[100]');
		$this->form_validation->set_rules('iso', t('ISO'), 'xss_clean|trim|required|max_length[3]');
		
		$country=NULL;
				
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
				$db_result=$this->country_model->insert($options);
			}
			else
			{
				$db_result=$this->country_model->update($id,$options);
			}
									
			if ($db_result===TRUE)
			{
				$this->session->set_flashdata('message', t('form_update_success'));
				redirect("admin/countries","refresh");
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
					$country=$this->country_model->select_single($id);
					
					if (!$country)
					{
						show_error("INVALID-ID");
					}
				
				}
		}

		//show form
		$content=$this->load->view('countries/edit',$country,true);									
		$this->template->write('content', $content,true);
	  	$this->template->render();								
	}
	
	function delete($id)
	{
		if(!is_numeric($id))
		{
			show_error("INVALID_PARAM");
		}
		
		$this->country_model->delete($id);
		redirect("admin/countries");
	}
	
	
	
	
}    