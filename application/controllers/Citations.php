<?php
class Citations extends MY_Controller {

	var $active_repo=NULL;

    public function __construct()
    {
        parent::__construct($SKIP=TRUE);

		$this->template->set_template('default');
		$this->load->model('Citation_model');
		$this->load->model('Resource_model');
		$this->load->helper(array ('querystring_helper','url', 'form') );
		$this->load->library( array('form_validation','pagination') );
		$this->load->library('chicago_citation');

		$this->lang->load('general');
		$this->lang->load('citations');
		//$this->output->enable_profiler(TRUE);

		//set template for print
		if ($this->input->get("print")==='yes'){
			$this->template->set_template('blank');
		}
	}

	function index()
	{
		$repo=$this->get_repo_by_id($this->input->get("collection"));
		$collection='central';

		if($repo){
			$collection=$repo['repositoryid'];
		}

		$data['rows']=$this->_search();
		$data['active_repo']=$collection;
		$content=$this->load->view('citations/public_search', $data,true);

		$facet_options=array();

		$facet_options['ctypes']=$this->Citation_model->get_types_with_count($collection);
		$facet_options['active_repo']=$collection;

		$facet_options['search']=array(
			'keywords'=>$this->input->get("keywords"),
			'from'=>$this->input->get("from"),
			'to'=>$this->input->get("to"),
			'ctype'=>(array)$this->input->get("ctype")
		);

		//show search form
		$this->template->write('search_filters', $this->load->view('citations/facets',$facet_options,true),true);

		if ($collection!==''){
			$page_data=array(
				'repo'=>$repo,
				'active_tab'=>'citations',
				'repo_citations_count'=>$this->repository_model->get_citations_count_by_collection($collection),
				'content'=>$content
			);

			$content=$this->load->view("catalog_search/study_collection_tabs",$page_data,TRUE);
		}

		$this->template->write('title', t('citations'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}

	private function get_repo_by_id($repoid)
	{
		if (!$repoid){
			return FALSE;
		}

		$this->load->model("repository_model");
		return $this->repository_model->get_repository_by_repositoryid($repoid);
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
		$collection=$this->input->get('collection');

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'rank';

		//filter
		$filter=NULL;

		$search_options=array(
			'keywords'=>$this->input->get("keywords"),
			'from'=>$this->input->get("from"),
			'to'=>$this->input->get("to"),
			'ctype'=>$this->input->get("ctype")
		);

		//records
		$rows=$this->Citation_model->search($per_page, $offset,$search_options, $sort_by, $sort_order,$published=1,$repository=$collection);

		//total records found
		$total = $this->Citation_model->search_count();

		if ($offset>$total){
			$offset=$total-$per_page;

			//search again
			$rows=$this->Citation_model->search($per_page, $offset,$filter, $sort_by, $sort_order,$published=1,$repository=$collection);
		}

		//set pagination options		
		$base_url = site_url('citations');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment']="offset";
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field','sort_by','sort_order','collection','ctype','from','to'));//pass any additional querystrings
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<ul class="pagination pagination-md page-nums">' ;
		$config['full_tag_close'] = '</ul>';
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
		if (!is_numeric($citationid)){
			show_404();
		}

		$citation=$this->Citation_model->select_single($citationid);

		if (!$citation){
			show_404();
		}

		$citation['repo_citations_count']=$this->repository_model->get_citations_count_by_collection($this->active_repo['repositoryid']);

		$content=$this->load->view('citations/citation_info',$citation,TRUE);
		//$content.='<div class="citation-box">'.$this->chicago_citation->format($citation,'journal').'</div>';

		$repo=$this->get_repo_by_id($this->input->get("collection"));
		$collection='central';

		if($repo){
			$collection=$repo['repositoryid'];
		}

		if ($collection!==''){
			$content=$this->load->view("catalog_search/study_collection_tabs",array('content'=>$content,'repo'=>$repo,'active_tab'=>'citations'),TRUE);
		}


		//change template if ajax request
		if ($this->input->get_post("ajax") || $this->input->get_post("print")){
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
		if (!is_numeric($citationid)){
			show_404();
		}

		$citation=$this->Citation_model->select_single($citationid);

		header("Content-Type: text/plain");
		$this->load->view('citations/export_bibtex', array('bib'=>$citation));
		//$this->load->library('bibtex');
		//echo $this->bibtex->export($citation);
	}



	function export_all($format='html'){
		$this->db->select('*');
		$citations=$this->db->get('citations')->result_array();
		//$this->load->view('citations/export_to_html',$data);

		$filename='citations-'.date("m-d-y-his").'.csv';
		header('Content-Encoding: UTF-8');
		header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment;filename='.$filename);
        $fp = fopen('php://output', 'w');

		echo "\xEF\xBB\xBF"; // UTF-8 BOM

		//add column names
		fputcsv($fp, array_keys($citations[0]));

		foreach($citations as $citation){
			$citation['changed']=date("M-d-y",$citation['changed']);
			$citation['created']=date("M-d-y",$citation['created']);
			fputcsv($fp, $citation);
		}

		fclose($fp);
	}


	function _show_citations_by_collection($repository_id){
		$this->load->model("repository_model");
		$repository_id=strtolower($repository_id);

		//get an array of all valid repository names from db
		$repositories=$this->repository_model->get_repository_array();
		$repositories[]='central';

		//repo names to lower case
		foreach($repositories as $key=>$value){
			$repositories[$key]=strtolower($value);
		}

		//check if URI matches to a repository name
		if (in_array($repository_id,$repositories)){
			//repository options
			if ($repository_id=='central'){
				$this->active_repo=NULL;
				$this->session->set_userdata('active_repository','');
			}
			else{
				//add repo filter
				$this->active_repo=$this->repository_model->get_repository_by_repositoryid($repository_id);

				//save active repository name in session
				$this->session->set_userdata('active_repository',$repository_id);
			}

			//load the default listing page
			$this->index();
		}
		else{
			show_404();
		}
	}



	function _remap()
	{
		$method=$this->uri->segment(2);

		//if no method, load the default page
		if(!$method)
		{
			$this->index();	return;
		}

		switch($method){
			case 'collection':
			case 'by-collection':
				$this->_show_citations_by_collection($this->uri->segment(3));
			break;

			//show citations by id
			case is_numeric($method):
				$action=$this->uri->segment(3);

				if ($action=='export'){
					$this->export($method);
				}
				else{
					//default view
					$this->view($method);
				}

			break;
			case 'export_all':
				$this->export_all();
			break;

			default:
				$this->index();
		}
	}
}
/* End of file citations.php */
/* Location: ./controllers/citations.php */
