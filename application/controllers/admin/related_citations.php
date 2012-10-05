<?php

class Related_Citations extends MY_Controller {

	var $active_repo=NULL;
	var $sess_id=NULL;
	var $selected_citations=array();
 
    public function __construct()
    {
        parent::__construct();
       	$this->load->model('Citation_model');
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
		$this->related_citations=(array)$this->session->userdata($skey);
			
		//css files
		$this->template->add_css('themes/admin/catalog_admin.css');
		
		//js & css for jquery window 
		$this->template->add_css('javascript/jquery/themes/ui-lightness/jquery-ui-1.7.2.custom.css');
		$this->template->add_js('javascript/jquery/ui/jquery-ui-1.7.2.custom.js');
				
		//get citations		
		$db_rows=$this->_search($skey);
		//load the contents of the page into a variable
		$content=$this->load->view('catalog/related_citations_index', $db_rows,true);
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	
	private function _search($skey)
	{
		$session_id="citations";

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

		$this->field=		$this->input->get('field');
		$this->keywords=	$this->input->get('keywords');
		$this->sort_order=	$this->input->get('sort_order') ? $this->input->get('sort_order') : 'desc';
		$this->sort_by=		$this->input->get('sort_by');
		//filter
		$filter=NULL;

		//simple search
		if ($this->keywords){
			$filter[0]['field']=$this->field;
			$filter[0]['keywords']=$this->keywords;
		}	

		//records
		$data['rows']=$this->Citation_model->search($per_page, $curr_page ,$filter, $this->sort_by, $this->sort_order);

		//total records in the db
		$total = $this->Citation_model->search_count();

		if ($curr_page>$total)
		{
			$curr_page=$total-$per_page;
			
			//search again
			$data['rows']=$this->Citation_model->search($per_page, $curr_page,$filter);
		}
		//set pagination options
		$base_url = site_url('admin/related_citations/index/'.$skey);
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
	* Add citations to session by key
	**/	
	public function add($skey,$cid,$isajax=0)
	{
		$id_list=explode(",",$cid);
		
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
		$related_citations[$skey] = $sess_data;
		$this->session->set_userdata($related_citations);
		$this->Citation_model->attach_related_surveys($cid, array($skey));	
		if ($isajax==0)
		{
			//redirect('/admin/related_citations/index/'.$skey.'/?per_page='.$this->session->userdata('oldurl'));
		}	
	}
	
	
	/**
	*
	* Remove citations from session using key
	**/
	public function remove($skey,$cid,$isajax=0)
	{
		if (!is_numeric($cid))
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
			if ($value==$cid)
			{
				unset($sess_data[$key]);
				break;
			}
		}
		

		//update session
		$related_citations[$skey] = $sess_data;
		$this->session->set_userdata($related_citations);
		$this->Citation_model->delete_related_survey($cid, array($skey));	
		if($isajax==0)
		{
			redirect('/admin/related_citations/index/'.$skey.'/?per_page='.$this->session->userdata('oldurl'));
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
	
 