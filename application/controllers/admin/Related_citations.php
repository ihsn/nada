<?php

class Related_Citations extends MY_Controller {

    public function __construct()
    {
      parent::__construct();
      $this->load->model('Citation_model');
      $this->load->model('Catalog_model');
      $this->load->library('pagination');
      $this->load->helper('querystring_helper','url');
      $this->load->helper('form');
      $this->load->helper("catalog");
      $this->template->set_template('admin_blank');
      $this->load->library('chicago_citation');

      //load language file
      $this->lang->load('general');
      $this->lang->load('catalog_search');
      $this->lang->load('catalog_admin');

		//$this->output->enable_profiler(TRUE);
	}


	/**
	*
	* @id	survey id
	**/
	public function index($skey)
	{
  		if (!is_numeric($skey)){
  			return FALSE;
  		}

      $survey=$this->Catalog_model->get_survey($skey);

      if (!$survey)
      {
        show_error("SURVEY NOT FOUND");
      }

      //get citations
      $db_rows=$this->_search($skey);

      //survey id
      $db_rows['survey_id']=$skey;

      $db_rows['survey_title']=$survey['title'];

      //list of attached citations to survey
      $db_rows['selected_citations']=$this->Citation_model->get_citations_id_array_by_survey($skey);

      //load the contents of the page into a variable
      $content=$this->load->view('catalog/related_citations_index', $db_rows,true);

      //pass data to the site's template
      $this->template->write('content', $content,true);

      //render final output
      $this->template->render();
	}


	private function _search($skey)
	{
		//records to show per page
		$per_page = $this->input->get("ps");

		if($per_page===FALSE || !is_numeric($per_page)){
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

    if (trim($this->field)==''){
      $this->field='all';
    }
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

		if ($curr_page>$total){
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
	* Add a single citation to a single survey
	*
	* @sid survey id
	* @cid	citation id
	**/
	public function add($sid=NULL,$cid=NULL)
	{
		if (!is_numeric($cid) || !is_numeric($sid))
		{
			show_error("INVALID_ID");
		}

		//update database
		$this->Citation_model->attach_related_surveys($cid, array($sid));
	}


	/**
	*
	* Remove related citations from survey
	**/
	public function remove($sid,$cid,$isajax=0)
	{
		if (!is_numeric($cid) || !is_numeric($sid))
		{
			show_error("INVALID_ID");
		}

		$this->Citation_model->delete_related_survey($cid, array($sid));
	}


}//end-class
