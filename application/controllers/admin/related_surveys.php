<?php

class Related_Surveys extends MY_Controller {

	var $active_repo=NULL;
	var $sess_id=NULL;
	var $selected_surveys=array();
 
    public function __construct()
    {
        parent::__construct();
       	$this->load->model('Catalog_model');
		$this->load->library('pagination');
		$this->load->helper('querystring_helper','url');
		$this->load->helper('form');
		$this->load->helper("catalog");
		$this->template->set_template('blank');
		
		//load language file
		$this->lang->load('general');
    	$this->lang->load('catalog_search');
		$this->lang->load('catalog_admin');

		//$this->output->enable_profiler(TRUE);
	}
	
	
	/**
	*
	* @id	session key
	**/
	public function index($skey){	
		$this->sess_id=$skey;		
		$this->related_surveys=(array)$this->session->userdata($skey);
			
		//css files
		$this->template->add_css('themes/admin/catalog_admin.css');
		
		//js & css for jquery window 
		$this->template->add_css('javascript/jquery/themes/ui-lightness/jquery-ui-1.7.2.custom.css');
		$this->template->add_js('javascript/jquery/ui/jquery-ui-1.7.2.custom.js');
		
		//set filter on active repo
		$this->Catalog_model->active_repo=NULL;
		
		//get surveys		
		$db_rows=$this->_search($skey);
		//load the contents of the page into a variable
		$content=$this->load->view('citations/related_surveys_index', $db_rows,true);
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	
	private function _search($skey)
	{
		//records to show per page
		$per_page = $this->input->get("ps");
		
		if($per_page===FALSE || !is_numeric($per_page))
		{
			$per_page=10;
		}
				
		//current page
		$curr_page=$this->input->get('per_page');//$this->uri->segment(4);

		//filter to further limit search
		$filter=array();
		
		//records
		$data['rows']=$this->Catalog_model->search($per_page, $curr_page,$filter);

		$this->session->set_userdata('oldurl', $this->input->get('per_page'));

		//total records in the db
		$total = $this->Catalog_model->search_count;

		if ($curr_page>$total)
		{
			$curr_page=$total-$per_page;
			
			//search again
			$data['rows']=$this->Catalog_model->search($per_page, $curr_page,$filter);
		}
		//set pagination options
		$base_url = site_url('admin/related_surveys/index/'.$skey);
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('id', 'sort_by','sort_order','keywords', 'field','ps'));//pass any additional querystrings
		$config['next_link'] = t('page_next');
		$config['num_links'] = 5;
		$config['prev_link'] = t('page_prev');
		$config['first_link'] = t('page_first');
		$config['last_link'] = t('last');
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $data;		
	}
	
	
	/**
	*
	* Add surveys to session by key
	**/	
	public function add($skey,$sid,$isajax=0)
	{
		$id_list=explode(",",$sid);
		
		foreach($id_list as $key=>$value)
		{
			if (!is_numeric($value))
			{
				unset($id_list[$key]);
			}
		}
		
		//get session data by key
		$sess_data=$this->session->userdata($skey);
		
		//create empty error if not an array
		if (!is_array($sess_data))
		{
			$sess_data=array();
		}
				
		//add survey to array 
		foreach($id_list as $key=>$value)
		{
			if (!in_array($value,$sess_data))
			{
				$sess_data[]=(int)$value;
			}
		}
				
		//update session
		$related_surveys[$skey] = $sess_data;
		$this->session->set_userdata($related_surveys);
		
		if ($isajax==0)
		{
		//	redirect('/admin/related_surveys/index/'.$skey.'/?per_page='.$this->session->userdata('oldurl'));
		}	
	}
	
	
	/**
	*
	* Remove surveys from session using key
	**/
	public function remove($skey,$sid,$isajax=0)
	{
		if (!is_numeric($sid))
		{
			show_error("INVALID_ID");
		}
	
		//get session data by key
		$sess_data=$this->session->userdata($skey);
		
		//create empty error if not an array
		if (!is_array($sess_data))
		{
			$sess_data=array();
		}
		
		//remove survey from array 
		foreach($sess_data as $key=>$value)
		{
			if ($value==$sid)
			{
				unset($sess_data[$key]);
				break;
			}
		}
		

		//update session
		$related_surveys[$skey] = $sess_data;
		$this->session->set_userdata($related_surveys);
		
		if($isajax==0)
		{
		//	redirect('/admin/related_surveys/index/'.$skey.'/?per_page='.$this->session->userdata('oldurl'));
		}
	}
	
	/**
	*
	* Remove all session data for a key
	**/
	function clear_all($skey)
	{
		$this->session->unset_userdata($skey);
	}
	
}
	
 