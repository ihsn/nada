<?php

class Related_Surveys extends MY_Controller {

	var $active_repo=NULL; //active repo object
 
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
		$this->lang->load('resource_manager');

	}
	
	public function index(){	

		if ($this->input->get('attach')) {
			if (!isset($this->session->userdata[$this->input->get('id')]) &&
			empty($this->session->userdata[$this->input->get('id')])) {
				$this->session->userdata[$this->input->get('id')] = array();
				array_push($this->session->userdata[$this->input->get('id')], $this->input->get('attach'));
				$this->session->sess_write();
			} else {
				$this->session->userdata[$this->input->get('id')][] = $this->input->get('attach');
				$this->session->sess_write();
				var_dump($this->session->userdata[$this->input->get('id')]);
			}
			exit; // end here for ajax calls
		}

		//css files
		$this->template->add_css('themes/admin/catalog_admin.css');
		
		//js & css for jquery window 
		$this->template->add_css('javascript/jquery/themes/ui-lightness/jquery-ui-1.7.2.custom.css');
		$this->template->add_js('javascript/jquery/ui/jquery-ui-1.7.2.custom.js');
		
		//set filter on active repo
		if (isset($this->active_repo) && $this->active_repo!=null)
		{
			//$filter=$this->Catalog_model->filter;
//			$this->Catalog_model->filter['repositoryid=']=$this->active_repo->repositoryid;
			$this->Catalog_model->active_repo=$this->active_repo->repositoryid;
		}
		
		//get surveys		
		$db_rows=$this->_search();
		//load the contents of the page into a variable
		$content=$this->load->view('citations/related_surveys_index', $db_rows,true);
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	
	private function _search()
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
		
		/*if (isset($this->active_repo) && $this->active_repo!=null)
		{
			$filter=array('repositoryid'=>$this->active_repo->repositoryid);
		}*/
		
		//records
		$data['rows']=$this->Catalog_model->search($per_page, $curr_page,$filter);

		//total records in the db
		$total = $this->Catalog_model->search_count;

		if ($curr_page>$total)
		{
			$curr_page=$total-$per_page;
			
			//search again
			$data['rows']=$this->Catalog_model->search($per_page, $curr_page,$filter);
		}
		//set pagination options
		$base_url = site_url('admin/related_surveys');
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
}
	
 