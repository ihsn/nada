<?php
class Citations extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($SKIP=TRUE);
		
		$this->template->set_template('default');	
		//$this->template->write('sidebar', $this->_menu(),true);	
		//$this->template->add_css('css/admin.css');		
       	
		$this->load->model('Citation_model');
		$this->load->model('Resource_model');
		$this->load->helper(array ('querystring_helper','url', 'form') );		
		$this->load->library( array('form_validation','pagination') );
		$this->load->library('chicago_citation');
		
		$this->lang->load('general');
		$this->lang->load('citations');
		//$this->output->enable_profiler(TRUE);
		
		//set template for print
		if ($this->input->get("print")==='yes')
		{
			$this->template->set_template('blank');
		}
	}
 
	function index()
	{	
		//$this->Citation_model->move_citation_authors();
		//$this->Citation_model->update_citation_author_array_tostring();
		
		//get records		
		$data['rows']=$this->_search();
		
		$content=$this->load->view('citations/public_search', $data,true);
	
		$this->template->write('title', t('citations'),true);
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	
	/**
	* returns the paginated result
	* 
	* supports: sorting, searching, pagination
	*/
	function _search()
	{
		//records to show per page
		$per_page = 30;
				
		//current page
		$offset=(int)$this->input->get('offset');//$this->uri->segment(4);

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'authors';

		//filter
		$filter=NULL;

		//simple search
		if ($this->input->get_post("keywords") ){
			$filter[0]['field']=$this->input->get_post('field');
			$filter[0]['keywords']=$this->input->get_post('keywords');			
		}		
		
		//records
		$rows=$this->Citation_model->search($per_page, $offset,$filter, $sort_by, $sort_order,$published=1);

		//total records found
		$total = $this->Citation_model->search_count();

		if ($offset>$total)
		{
			$offset=$total-$per_page;
			
			//search again
			$rows=$this->Citation_model->search($per_page, $offset,$filter, $sort_by, $sort_order,$published=1);
		}
		
		//set pagination options
		$base_url = site_url('citations');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment']="offset"; 
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field','sort_by','sort_order'));//pass any additional querystrings
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		$config['last_link'] = '&raquo;';
		$config['first_link'] = '&laquo;';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $rows;		
	}
	
	/**
	*
	* Show citation by id
	*
	**/
	function view($citationid=NULL)
	{
		if ( !is_numeric($citationid))
		{
			show_404();
		}
		
		$citation=$this->Citation_model->select_single($citationid);
		
		if (!$citation)
		{
			show_404();
		}
		
		$content=$this->load->view('citations/citation_info',$citation,TRUE);
		$content.='<div class="citation-box">'.$this->chicago_citation->format($citation,'journal').'</div>';

		//change template if ajax request
		if ($this->input->get_post("ajax")!==false || $this->input->get_post("print")!==false)
		{
			$this->template->set_template('blank');
		}
		
		$this->template->write('title', $citation['title'],true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}

	/**
	*
	* Export citation
	*
	**/
	function export($citationid=NULL,$format='bibtex')
	{
		if ( !is_numeric($citationid))
		{
			show_404();
		}
		
		$citation=$this->Citation_model->select_single($citationid);
		header("Content-Type: text/plain");
		$this->load->view('citations/export_bibtex', array('bib'=>$citation));
		//$this->load->library('bibtex');
		
		//echo $this->bibtex->export($citation);
	}
	
	
	
	
	function _remap()
	{
		$method=$this->uri->segment(2);
		
		//if no method, load the default page
		if($method===FALSE)
		{
			$this->index();	return;
		}

		switch($method)
		{
			//show citations by id
			case is_numeric($method):
				$action=$this->uri->segment(3);
				
				if ($action=='export')
				{
					$this->export($method);
				}
				else
				{
					//default view
					$this->view($method);
				}
					
			break;
			
			default:
				$this->index();	
		}
		
	}
}
/* End of file citations.php */
/* Location: ./controllers/citations.php */