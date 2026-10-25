<?php
class Citations extends MY_Controller {

	var $active_repo=NULL;

    public function __construct()
    {
        parent::__construct($SKIP=TRUE);

		$this->template->set_template('default');
		$this->load->model('Citation_model');
		$this->load->model('Survey_resource_model');
		$this->load->helper(array ('querystring_helper','url', 'form') );
		$this->load->library( array('form_validation','pagination') );
		$this->load->library('chicago_citation');

		$this->lang->load('general');
		$this->lang->load('citations');
		$this->lang->load('catalog_search'); // result pager strings
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
		$data['pager_text']=$this->pager_text;
		$data['pager_links']=$this->pager_links;
		$data['pager_note']=$this->pager_note;
		$data['active_repo']=$collection;
		$data['repo']=$repo;
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
		$facets_html=$this->load->view('citations/facets',$facet_options,true);

		if ($collection!==''){
			$page_data=array(
				'repo'=>$repo,
				'active_tab'=>'citations',
				'repo_citations_count'=>$this->repository_model->get_citations_count_by_collection($collection),
				'content'=>$content,
				'facets_html'=>$facets_html
			);

			$content=$this->load->view("citations/citations_search",$page_data,TRUE);
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


	/** Set by _search() for the view: "X-Y of N", the pager links (HTML), and a note under them ('' for none). */
	public $pager_text = '';
	public $pager_links = '';
	public $pager_note = '';

	/** Query string fields a page link keeps. */
	private $pager_fields = array('keywords', 'field', 'sort_by', 'sort_order', 'collection', 'ctype', 'from', 'to');

	/**
	* returns the paginated result
	*
	* supports: sorting, searching, pagination
	*
	* Pages are numbered (?offset=) as far as the search engine reaches by offset: every row for the database search;
	* nada-ai's citations_max_offset rows, then Previous/Next by cursor (?cursor=, see Citation_model::$search_paging).
	*/
	function _search()
	{
		//records to show per page
		$per_page = 30;

		//current page
		$offset=max(0, (int)$this->input->get('offset'));
		$cursor=trim((string) xss_clean($this->input->get('cursor')));
		$collection=$this->input->get('collection');

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'rank';

		$search_options=array(
			'keywords'=>$this->input->get("keywords"),
			'from'=>$this->input->get("from"),
			'to'=>$this->input->get("to"),
			'ctype'=>$this->input->get("ctype")
		);

		$search = function ($offset, $cursor) use ($per_page, $search_options, $sort_by, $sort_order, $collection) {
			$this->Citation_model->search_cursor = $cursor;
			return $this->Citation_model->search($per_page, $offset, $search_options, $sort_by, $sort_order, $published=1, $repository=$collection);
		};

		try {
			$rows = $search($offset, $cursor);
		}
		catch (Semantic_search_api_exception $e) {
			if ($e->error_code() === 'cursor_invalid') {
				// a cursor of another search (edited, or kept from another sort): the first page
				$offset = 0;
				$cursor = '';
				$rows = $search($offset, $cursor);
			}
			elseif ($e->error_code() === 'offset_out_of_range' && isset($e->response['error']['details']['max_offset'])) {
				// a page number past what offsets reach (typed in): the last numbered page
				$max_offset = (int) $e->response['error']['details']['max_offset'];
				$offset = max(0, (int) (floor(($max_offset - $per_page) / $per_page) * $per_page));
				$cursor = '';
				$rows = $search($offset, $cursor);
			}
			else {
				throw $e;
			}
		}

		//total records found
		$total = $this->Citation_model->search_count();
		$paging = $this->Citation_model->search_paging;

		// a numbered page past the last row (an old or typed-in link): the last numbered page that has rows
		if ($cursor === '' && $total > 0 && $offset >= $total){
			$reach = $paging !== NULL ? min($total, (int) $paging['max_offset']) : $total;
			$offset = (int) (floor(($reach - 1) / $per_page) * $per_page);

			//search again
			$rows=$search($offset, '');
			$total = $this->Citation_model->search_count();
			$paging = $this->Citation_model->search_paging;
		}

		$this->set_pager($rows, $total, $offset, $per_page, $cursor !== '' ? $paging : NULL, $paging);
		return $rows;
	}

	/**
	 * The pager for the view: numbered links (MY_Pagination) for the rows offsets reach, and past them (nada-ai) the
	 * cursor links.
	 *
	 * @param array      $rows        this page
	 * @param int        $total       rows found
	 * @param int        $offset      requested offset (offset paging)
	 * @param array|null $cursor_page the paging of a cursor page, or NULL on a numbered page
	 * @param array|null $paging      the search's paging (NULL: numbered pages reach every row)
	 */
	private function set_pager(array $rows, $total, $offset, $per_page, $cursor_page, $paging)
	{
		$first = ($cursor_page !== NULL ? (int) $cursor_page['offset'] : $offset) + 1;
		$this->pager_text = $rows ? sprintf(t('showing %s-%s of %s'), $first, $first + count($rows) - 1, $total) : '';

		$query = get_querystring($this->pager_fields);
		$link = function ($label, $url) {
			return '<a class="page-link" href="'.html_escape($url).'">'.$label.'</a>';
		};
		$cursor_url = function ($token) use ($query) {
			return site_url('citations').'/?'.($query !== '' ? $query.'&' : '').'cursor='.rawurlencode($token);
		};

		if ($cursor_page !== NULL) {
			// past the numbered pages: back to the first page, Previous, Next
			$links = $link(sprintf(t('pager_page_n'), 1), site_url('citations').'/?'.$query);
			if (!empty($cursor_page['prev_cursor'])) {
				$links .= $link('&lsaquo; '.t('pager_prev'), $cursor_url($cursor_page['prev_cursor']));
			}
			if (!empty($cursor_page['next_cursor'])) {
				$links .= $link(t('pager_next').' &rsaquo;', $cursor_url($cursor_page['next_cursor']));
			}
			$this->pager_links = '<ul class="pagination pagination-md page-nums">'.$links.'</ul>';
			$this->pager_note = sprintf(t('pager_past_numbered'), number_format((int) $cursor_page['max_offset']));
			return;
		}

		// numbered pages: as far as offsets reach (nada-ai: max_offset rows)
		$numbered = $paging !== NULL ? min($total, (int) $paging['max_offset']) : $total;
		$config['base_url'] = site_url('citations');
		$config['total_rows'] = $numbered;
		$config['per_page'] = $per_page;
		$config['query_string_segment']="offset";
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=$query;//pass any additional querystrings
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<ul class="pagination pagination-md page-nums">' ;
		$config['full_tag_close'] = '</ul>';
		$config['last_link'] = '&raquo;';
		$config['first_link'] = '&laquo;';
		$this->pagination->initialize($config);
		$this->pager_links = $this->pagination->create_links();
		$this->pager_note = '';

		// on the last numbered page of a longer result: Next continues by cursor
		if ($paging !== NULL && $total > $numbered && $offset + count($rows) >= $numbered && !empty($paging['next_cursor'])) {
			$next = $link(t('pager_next').' &rsaquo;', $cursor_url($paging['next_cursor']));
			$this->pager_links = $this->pager_links !== ''
				? preg_replace('#</ul>$#', $next.'</ul>', $this->pager_links)
				: '<ul class="pagination pagination-md page-nums">'.$next.'</ul>';
			$this->pager_note = sprintf(t('pager_past_numbered'), number_format($numbered));
		}
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

		//$citation['repo_citations_count']=$this->repository_model->get_citations_count_by_collection($this->active_repo['repositoryid']);

		$content=$this->load->view('citations/citation_info',$citation,TRUE);
		//$content.='<div class="citation-box">'.$this->chicago_citation->format($citation,'journal').'</div>';

		/*$repo=$this->get_repo_by_id($this->input->get("collection"));
		$collection='central';

		if($repo){
			$collection=$repo['repositoryid'];
		}

		if ($collection!==''){
			$content=$this->load->view("catalog_search/study_collection_tabs",array('content'=>$content,'repo'=>$repo,'active_tab'=>'citations'),TRUE);
		}*/


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
