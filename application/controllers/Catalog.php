<?php
class Catalog extends MY_Controller {

		//active repository object
		var $active_repo=NULL;

		var $active_repo_id=NULL;

		//active tab - default ALL
		var $active_tab=NULL;

		//facets data + count
		var $facets= array();

		//enable filters
		var $enabled_filters=array();

		//list of all filters
		var $filters_list=array();

    public function __construct()
	{
		parent::__construct($skip_auth=TRUE);

		$this->template->set_template('default');
		$this->load->helper('pagination_helper');
		$this->load->model('Search_helper_model');
		$this->load->model('Catalog_model');
		$this->load->model('Vocabulary_model');
		$this->load->model('Repository_model');
		$this->load->model('Form_model');
		$this->load->model('Data_classification_model');
		$this->load->model('Facet_model');

		//load facets configurations
		$this->load->config("facets");
		//$this->enabled_filters=$this->config->item('facets_by_type');

		//$this->output->enable_profiler(TRUE);
		$this->filters_list=array_keys($this->Facet_model->select_all());

		//language files
		$this->lang->load('general');
		$this->lang->load('catalog_search');

		//configuration settings
		//$this->topic_search=true;//($this->config->item("topic_search")===FALSE) ? 'no' : $this->config->item("topic_search");
		$this->regional_search=($this->config->item("regional_search")===FALSE) ? 'no' : $this->config->item("regional_search");
		$this->collection_search=($this->config->item("collection_search")===FALSE) ? 'no' : $this->config->item("collection_search");
		$this->da_search=($this->config->item("da_search")===FALSE) ? 'no' : $this->config->item("da_search");
		$this->data_types_nav_bar=$this->config->item("data_types_nav_bar");
		$this->search_box_orientation=$this->config->item("search_box_orientation")== FALSE ? 'default' : $this->config->item("search_box_orientation");
	}


	/**
	 * 
	 * 
	 * Get data type for the active tab/page
	 * 
	 */
	private function get_active_data_type()
	{
		if($this->active_tab==''){
			return 'all';
		}else
		{
			return $this->active_tab;
		}
	}


	private function set_enabled_filters($active_tab=null)
	{
		$tab=$this->get_active_data_type();

		if($tab=='survey'){
			$tab='microdata';
		}

		$this->enabled_filters=(array)json_decode($this->config->item('facets_'.$tab),true);
	}

	/**
	 * 
	 * Load data for all facets
	 * 
	 */
	private function load_facets_data($active_tab=null)
	{

		$facets=(array)$this->enabled_filters;
		$filters=$this->filters_list;


		//get years
		//if ($this->is_facet_enabled($this->active_tab,'year')){
			$years_range=$this->Search_helper_model->get_min_max_years();//get_years_range();
			$this->facets['years']=$years_range;
		//}

		$repo_id='';

		if(isset($this->active_repo['repositoryid'])){
			$repo_id=$this->active_repo['repositoryid'];
		}

		//core facets
		$this->facets['repositories']=$this->Search_helper_model->get_active_repositories(
			$this->active_tab,
			$this->input->get("collection")
		);

		$this->facets['regions']=array();
		if ($this->is_facet_enabled($this->active_tab,'region')){
			$this->facets['regions']=$this->Search_helper_model->get_active_regions(
				$repo_id,
				$this->active_tab,
				$this->input->get("region")
			);
		}
		
		$this->facets['da_types']=$this->Search_helper_model->get_active_data_types(
			$repo_id,
			$this->active_tab,
			$this->input->get("dtype")
		);

		$this->facets['data_class']=$this->Search_helper_model->get_active_data_classifications($repo_id);

		$this->facets['countries']=$this->Search_helper_model->get_active_countries(
			$repo_id, 
			$this->active_tab, 
			$this->input->get("country")
		);
				
		$this->facets['tags']=$this->Search_helper_model->get_active_tags(
			$repo_id,
			$this->active_tab,
			$this->input->get("tag")
		);
	
		$this->facets['types']=$this->Search_helper_model->get_dataset_types($repo_id);
		
		//load user defined facets from db
		$facets_list=$this->Facet_model->select_all();

		foreach($facets_list as $fc){
			if($fc['facet_type']=='user' 
					//&& $this->is_facet_enabled($this->active_tab,$fc['name'])
					){												
				$this->facets[$fc['name']]=array(
					'type'=>$fc['facet_type'],
					'title'=>$fc['title'],
					'values'=>$this->Facet_model->get_facet_values($fc['id'],
						$published=1,
						$sort_='value',
						$sort_order_='ASC',
						$this->active_tab,						
						$this->input->get($fc['name'])
					)
				);
			}
		}

	}


	private function load_facets_html()
	{
		//$filters=(array)$this->enabled_filters;
		$filters=$this->filters_list;

		//flip to keep the keys for sorting facets
		$filters=array_flip($filters);
		foreach($filters as $key=>$val){
			$filters[$key]=null;
		}		

		//tags
		$filters['tag']=$this->load->view('search/facet', 
			array(
				'items'=>$this->facets['tags'], 
				'filter_id'=>'tag',
				'is_enabled'=>$this->is_facet_enabled($this->active_tab,'tag')
			),true);
			
		$filters['year']=$this->load->view('search/filter_years',
			array(
				'years'=>$this->facets['years'],
				'is_enabled'=>$this->is_facet_enabled($this->active_tab,'year')
			),true);
		
		if(!isset($this->active_repo_id)){
			$filters['repositories']=$this->load->view('search/facet', 
			array(
				'items'=>$this->facets['repositories'], 
				'filter_id'=>'collection',
				'is_enabled'=>$this->is_facet_enabled($this->active_tab,'collection')
			),true);
		}

		//regions
		$filters['regions']=$this->load->view('search/facet', 
			array(
				'items'=>$this->facets['regions'], 
				'filter_id'=>'region',
				'is_enabled'=>$this->is_facet_enabled($this->active_tab,'region')
			),true);

		//data classifications
		$filters['data_class']=$this->load->view('search/facet', 
			array(
				'items'=>$this->facets['data_class'], 
				'filter_id'=>'data_class',
				'is_enabled'=>$this->is_facet_enabled($this->active_tab,'data_class')
			),true);


		//data types
		
		$filters['da_type']=$this->load->view('search/facet', 
			array(
				'items'=>$this->facets['da_types'], 
				'filter_id'=>'dtype',
				'is_enabled'=>$this->is_facet_enabled($this->active_tab,'dtype')
			),true);	
		
		//countries
		
		$filters['country']=$this->load->view('search/facet', 
			array(
				'items'=>$this->facets['countries'], 
				'filter_id'=>'country',
				'is_enabled'=>$this->is_facet_enabled($this->active_tab,'country')
			),true);
		
		//types filter
		if(!$this->active_tab){
			if ($this->is_facet_enabled($this->active_tab,'type')){
				$filters['data_type']=$this->load->view('search/facet', 
				array(
					'items'=>$this->facets['types'], 
					'filter_id'=>'type'
				),true);
			}
		}

		//user defined facets
		foreach($this->facets as $facet_key=>$facet){
			if(isset($facet['type']) && isset($facet['type'])=='user'){
				$filters[$facet_key]=$this->load->view('search/facet', 
				array(
					'items'=>$facet['values'],
					'filter_id'=>$facet_key,
					'title'=>$facet['title'],
					'is_enabled'=>$this->is_facet_enabled($this->active_tab,$facet_key)
				),true);
			}
		}
		return $filters;
	}


	private function is_facet_enabled($type,$facet)
	{
		if ($type==''){
			$type='all';
		}
		if ( in_array($facet,$this->enabled_filters)){
			return true;
		}
		return false;
	}

	private function get_facets_by_type($type)
	{
		if ($type==''){
			$type='all';
		}

		if (isset($this->enabled_filters[$type])){
			return $this->enabled_filters[$type];
		}
		return false;
	}

	
	/**
	 * 
	 * Load filters/facets, search interface and UI
	 * 
	 * calls the /search to load the search results
	 * 
	 */
	function index()
    {
		$this->active_tab=xss_clean((string)$this->input->get("tab_type"));
		$dataset_view=$this->get_type_pageview($this->active_tab);
		
		$output= $this->_search();
		$output['tab_type']=$this->active_tab;
		$output['facets']=$this->facets;
		
		//enable/disable types navbar tabs
		$output['data_types_nav_bar']=$this->data_types_nav_bar;
		$output['search_box_orientation']=$this->search_box_orientation;

		if ($output['search_type']=='variable'){
			$output['search_output']=$this->load->view('search/variables', $output,true);
		}
		else{
			$output['search_output']=$this->load->view($dataset_view, $output,true);
		}
		

		$filters_html=$this->load_facets_html();

		$output['filters']=$filters_html;

		//tabs
		$tabs=array();
		$tabs['types']=$this->facets['types'];

		//variable view is enabled
		if(isset($output['variables'])){
			$tabs['search_counts_by_type']=array();
			$tabs['active_tab']="survey";
		}else{
			$tabs['search_counts_by_type']=$output['surveys']['search_counts_by_type'];
			$tabs['active_tab']=xss_clean($this->input->get("tab_type"));
		}

		$output['tabs']=$tabs;		

		//load js
		$this->template->add_js('javascript/jquery.history.min.js');		

		$content=$this->load->view('search/layout',$output,true);
		$this->template->write('title', t('data_catalog'),true);
		$this->template->write('content', $content,true);
		$this->template->render();
	}
	

	/**
	 * 
	 * 
	 * Return search results without facts or filters
	 * 
	 */
	function search()
	{
		$this->active_tab=xss_clean($this->input->get("tab_type"));		
		$dataset_view=$this->get_type_pageview($this->active_tab);
		//$this->load_facets_data();

		$output= $this->_search();

		$output['facets']=$this->facets;
		$output['tab_type']=$this->active_tab;
		
		if ($output['search_type']=='variable'){
			return $this->load->view('search/variables', $output);
		}
		else{
			return $this->load->view($dataset_view, $output);
		}
	}


	function _search()
	{
		$this->load->helper('security');
		$this->set_enabled_filters();

		//load data for facets
		$this->load_facets_data();

		//get year min/max
		//$data['min_year']=$this->facets['years']['min_year'];
		//$data['max_year']=$this->facets['years']['max_year'];

		$search_options=new StdClass;
		$limit=$this->get_page_size();

		//page parameters
		$search_options->collection		=xss_clean($this->input->get("collection"));
		$search_options->sk				=trim(xss_clean($this->input->get("sk")));
		$search_options->vf				=xss_clean($this->input->get("vf"));
		$search_options->country		=xss_clean($this->input->get("country"));
		$search_options->region			=xss_clean($this->input->get("region"));
		$search_options->view			=xss_clean($this->input->get("view"));
		$search_options->image_view		=xss_clean($this->input->get("image_view"));
		$search_options->topic			=xss_clean($this->input->get("topic"));
		$search_options->from			=(int)xss_clean($this->input->get("from"));
		$search_options->to				=(int)xss_clean($this->input->get("to"));
		$search_options->sort_by		=xss_clean($this->input->get("sort_by"));
		$search_options->sort_order		=xss_clean($this->input->get("sort_order"));
		$search_options->page			=(int)xss_clean($this->input->get("page"));
		$search_options->page			=($search_options->page >0) ? $search_options->page : 1;		
		$search_options->dtype			=xss_clean($this->input->get("dtype"));
		$search_options->data_class		=xss_clean($this->input->get("data_class"));
		$search_options->tag			=xss_clean($this->input->get("tag"));
		$search_options->sid			=xss_clean($this->input->get("sid"));
		$search_options->type			=xss_clean($this->input->get("type"));
		$search_options->country_iso3	=xss_clean($this->input->get("country_iso3"));
		$search_options->tab_type		=xss_clean($this->input->get("tab_type"));
		$search_options->repo			=xss_clean($this->active_repo_id);
		$search_options->ps				=$limit;
		$offset=						($search_options->page-1)*$limit;

		foreach($this->facets as $facet_key=>$facet){
			if(isset($facet['type']) && isset($facet['type'])=='user'){
				$search_options->{$facet_key}=xss_clean($this->input->get($facet_key));
			}
		}

		//allowed fields for sort_by and sort_order
		$allowed_fields = array('year','title','nation','country','popularity','rank');
		$allowed_order=array('asc','desc');

		//load default sort options from config if not set
		if(empty($search_options->sort_by)){
			$search_options->sort_by=$this->config->item("catalog_default_sort_by");
			$search_options->sort_order=$this->config->item("catalog_default_sort_order");
		}

		//set default sort options, if passed values are not valid
		if (!in_array(trim($search_options->sort_by),$allowed_fields)){
			$search_options->sort_by='';
		}

		//default for sort order if no valid values found
		if (!in_array($search_options->sort_order,$allowed_order)){
			$search_options->sort_order='';
		}

		//log
		if ($this->input->get('sk')){		
			$this->db_logger->write_log('search',$this->input->get("sk").'/'.$this->input->get("vk"),'sk-vk');
		}

		//get list of all repositories
		$data['repositories']=$this->Search_helper_model->get_repositories_list($published=1);

		//country int code + name
		if (is_array($search_options->country) && count($search_options->country)>0){						
			$data['countries']=$this->Search_helper_model->get_countries_list($search_options->country);//$this->Search_helper_model->get_active_countries($this->active_repo['repositoryid']);
		}

		$data['tags']=array();//$this->Search_helper_model->get_active_tags($this->active_repo['repositoryid']);

		$data['active_repo']=$this->active_repo;
		$data['active_repo_id']=$this->active_repo_id;

		if($search_options->tab_type!=''){
			$search_options->type=$search_options->tab_type;
		}

		$params=array(
			'collections'=>$search_options->collection,
			'study_keywords'=>$search_options->sk,
			//'variable_keywords'=>$search_options->vk,
			'variable_fields'=>$search_options->vf,
			'countries'=>$search_options->country,
			'regions'=>$search_options->region,
			'from'=>$search_options->from,
			'to'=>$search_options->to,
			'tags'=>$search_options->tag,
			'sort_by'=>$search_options->sort_by,
			'sort_order'=>$search_options->sort_order,
			'repo'=>$search_options->repo,
			'dtype'=>$search_options->dtype,
			'data_class'=>$search_options->data_class,
			'sid'=>$search_options->sid,
			'type'=>$search_options->type,
            'country_iso3'=>$search_options->country_iso3,
		);

		foreach($this->facets as $facet_key=>$facet){
			if(isset($facet['type']) && isset($facet['type'])=='user'){
				$params[$facet_key]=xss_clean($this->input->get($facet_key));
			}
		}

		$this->load->library('catalog_search',$params);
		$data['is_regional_search']=$this->regional_search;
		
		if($search_options->view=='v'){			
			$data['variables']=$this->catalog_search->vsearch($limit,$offset);
			$data['search_type']='variable';
		}else{
			$data['surveys']=$this->catalog_search->search($limit,$offset);
			$data['search_type']='study';
		}

		$data['current_page']=$search_options->page;
		$data['search_options']=$search_options;
		$data['data_access_types']=$this->facets['da_types'];//$this->Form_model->get_form_list();
		$data['data_classifications']=$this->facets['data_class'];//$this->Data_classification_model->get_list();
		$data['regions']=$this->facets['regions'];
		$data['sid']=$search_options->sid;

		//show featured only if no filters or search 
		if (isset($data['surveys']['found']) && isset($data['surveys']['total']) && $data['surveys']['found']==$data['surveys']['total']){
			$data['featured_studies']=$this->get_featured_studies_by_repo ($this->active_repo_id,$this->active_tab);
		}

		//attach related collections
		if (isset($data['surveys']['rows'])){
			$sid_arr=array_values(array_column($data['surveys']['rows'],'id'));			
			$related_collections=$this->Search_helper_model->related_collections($sid_arr);
			$data['related_collections']=$related_collections;
		}
		return $data;
	}


	/**
	 * 
	 * 
	 * Variable search for a single Survey
	 * 
	 */
	function vsearch($sid=NULL)
	{
		if ($sid==NULL || !is_numeric($sid)){
			die(t('error_invalid_parameters'));			
		}

		$params=array(
			'study_keywords'=>$this->input->get_post('sk'),
			'variable_keywords'=>$this->input->get_post('sk'),
			'variable_fields'=>$this->input->get_post('vf'),
			'countries'=>$this->input->get_post('country'),			
			'from'=>$this->input->get_post('from'),
			'to'=>$this->input->get_post('to'),
			'sort_by'=>$this->input->get_post('sort_by'),
			'sort_order'=>$this->input->get_post('sort_order'),
			'repo'=>$this->input->get_post('repo')
		);

		$this->load->library('catalog_search',$params);
		$data['variables']=$this->catalog_search->v_quick_search($sid);
		$this->load->view("catalog_search/var_quick_list", $data); 
	}



	/**
	 * 
	 * Catalog history
	 * 
	 */
	function history()
	{
		$this->load->library("pagination");
		$this->load->model("Catalog_history_model");

		$per_page = $this->input->get("ps");

		if($per_page===FALSE || !is_numeric($per_page)){
			$per_page=100;
		}

		$curr_page=$this->input->get('per_page');
		$filter=array();
		$data['rows']=$this->Catalog_history_model->search($per_page, $curr_page,$filter);
		$total = $this->Catalog_history_model->search_count;

		if ($curr_page>$total){
			$curr_page=$total-$per_page;
			$data['rows']=$this->Catalog_history_model->search($per_page, $curr_page,$filter);
		}

		//set pagination options
		$base_url = site_url('catalog/history');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('sort_by','sort_order','keywords', 'field','ps'));//pass any additional querystrings
		$config['next_link'] = t('page_next');
		$config['num_links'] = 5;
		$config['prev_link'] = t('page_prev');
		$config['first_link'] = t('page_first');
		$config['last_link'] = t('last');
		$config['full_tag_open'] = '<ul class="pagination pagination-md page-nums">' ;
		$config['full_tag_close'] = '</ul>';
		
		$this->pagination->initialize($config);
		$content=$this->load->view('search/history', $data,true);
		$this->template->write('content', $content,true);
		$this->template->write('title', t('catalog_history'),true);
	  	$this->template->render();	
	}


	function export($format='print')
	{
		$output= $this->_search();

		switch($format){
			case 'print':
				$content=$this->load->view('search/surveys', $output,TRUE);
				$this->template->set_template('blank');
				$this->template->write('title', t('studies'),true);
				$this->template->write('content', $content,true);
				$this->template->render();
			break;

			case 'csv':
				$rows=$output['surveys']['rows'];
				$cols=explode(",",'id,idno,title,nation,authoring_entity,year_start,year_end,created,changed');
				$filename='search-'.date("m-d-y-his").'.csv';
				header('Content-Encoding: UTF-8');
				header( 'Content-Type: text/csv' );
				header( 'Content-Disposition: attachment;filename='.$filename);
				$fp = fopen('php://output', 'w');

				echo "\xEF\xBB\xBF"; // UTF-8 BOM

				//add column names
				fputcsv($fp, $cols);

				foreach($rows as $row){
					$data=array();
					foreach($cols as $col){
						$data[$col]=$row[$col];
					}

					if( isset($data['changed'])){
						$data['changed']=date("M-d-y",$data['changed']);
						$data['created']=date("M-d-y",$data['created']);
					}

					fputcsv($fp, $data);
				}

				fclose($fp);

			break;
		}
	}



	/**
	 * 
	 * Get pageview by dataset type
	 * 
	 */
	private function get_type_pageview($type) 
	{
		//default view
		$dataset_view='search/surveys';

		switch($type){
			case 'image':
			case 'video':
			case 'visualization':
			//case 'document':
			//case 'script':
			//case 'timeseries':
				$dataset_view='search/images';
				break;
			/*case 'table':
				$dataset_view='search/tables';
				break;	*/
			default:
				$dataset_view='search/surveys';
				break;
		}

		return $dataset_view;
	}


	/**
	 * 
	 * Get page size
	 * 
	 */
	private function get_page_size()
	{
		$page_size_min=15;
		$page_size_max=100;

		$page_size=(int)$this->input->get('ps');

		if($page_size>=$page_size_min && $page_size<=$page_size_max){
			return $page_size;
		}

		return 15;//default page size
	}


	function _remap($method)
	{
		$method=strtolower($method);

		if ($method=='search'){
			$this->_set_active_repo($this->input->get("repo"));
			$this->search();
		}
		else if (in_array(($method), array_map('strtolower', get_class_methods($this))) ){
		  $uri = $this->uri->segment_array();
          unset($uri[1]);
          unset($uri[2]);
          call_user_func_array(array($this, $method), $uri);
		}
		else{

			//get repository id
			$repo_code=$this->uri->segment(2);

			//set active repos
			$this->_set_active_repo($method);
			
			//valid repo?
			if ($this->active_repo || $repo_code=='central'){
				//about?
				if ($this->uri->segment(3)=='about'){
					$this->about_repository();
					return;
				}
				//load the default listing page
				$this->index();
			}
			else{
				//show_404();
				$this->index();
			}
		}
	  }
	  

	private function _set_active_repo($repo)
	{
		$this->load->model("repository_model");
		$repo=(string)$repo;

		$repo=trim(strtolower($repo));
		//get an array of all valid repository names from db
		$repositories=$this->Catalog_model->get_repository_array();
		$repositories[]='central';

		//repo names to lower case
		foreach($repositories as $key=>$value){
			$repositories[$key]=strtolower($value);
		}

		//check if URI matches to a repository name
		if (in_array($repo,$repositories)){
			//repository options
			if ($repo=='central'){
				$this->active_repo=null;
				$this->active_repo_id=null;
				//$this->active_repo=$this->repository_model->get_central_catalog_array();
			}
			else{
				//set active repo
				$this->active_repo=$this->repository_model->get_repository_by_repositoryid($repo);
				$this->active_repo_id=$this->active_repo['repositoryid'];
			}
		}
	}


	function about_repository()
	{		
		$repositoryid=$this->uri->segment(2);
		redirect('collections/'.$repositoryid);		
	}



	/**
	*
	* Perform variable comparison
	*
	**/
	function compare($option=NULL, $format=NULL)
	{		
		$this->lang->load('ddi_fields');
		$this->lang->load('catalog_search');
		$this->load->model("Dataset_model");
		$this->load->model("Variable_model");
		$this->load->model("Data_file_model");
		$this->load->helper("metadata_view");		

		$items=explode(",",$this->input->cookie('variable-compare', TRUE));
		$list=array();

		if (!$items){
			return false;
		}

		//JSON /CSV export
		if ($option=='export'){
			if($format=='json' || $format=='csv'){
				foreach($items as $item=>$value){
					$tmp=explode('/',$value);
					if (isset($tmp[1])){
						$item_data=array();

						$dataset=$this->Dataset_model->get_row($tmp[0]);
						$variable=$this->Variable_model->get_var_by_vid($tmp[0],$tmp[1]);

						$item_data=$variable['metadata'];
						$item_data['sid']=$tmp[0];
						$item_data['survey_idno']=$dataset['idno'];
						
						$list[]=$item_data;
					}
				}

				if ($format=='json'){
					$this->output
						->set_content_type('application/json')
						->set_output(json_encode($list));
					return;
				}
				else if($format=='csv'){
					$this->Variable_model->export($list,'csv');
					return;
				}
			}
		}

		$view_types=array(
			'survey'=>'variable_ddi',
			'geospatial'=>'geospatial_features'
		);

		foreach($items as $item=>$value){
			$tmp=explode('/',$value);
			if (isset($tmp[1])){
				$item_data=array(
					'sid'=>$tmp[0], 
					'vid'=>$tmp[1],
					'variable'=>$this->Variable_model->get_var_by_vid($tmp[0],$tmp[1]),
					'file'=>$this->Data_file_model->get_file_by_varid($tmp[0],$tmp[1]),
					'dataset'=>$this->Dataset_model->get_row($tmp[0])
				);

				$view_name=isset($view_types[$item_data['dataset']['type']]) ? $view_types[$item_data['dataset']['type']] : 'survey';
				$item_data['html']=$this->load->view('survey_info/'.$view_name,$item_data,true);
				$list[]=$item_data;
			}
		}		
		
		$data['list']=$list;

		if ($option=='print'){
			if ($format!=='pdf'){
				echo $this->load->view("catalog_search/compare_print",$data,true);exit;
			}
			else if ($format==='pdf'){
				$this->load->library('pdf_export');
				$contents=$this->load->view("catalog_search/compare_print",$data,TRUE);
				$this->pdf_export->create_pdf($contents);
				exit;
			}
		}

		$this->template->set_template('blank');
		$this->template->add_js('javascript/dragtable.js');
		$content=$this->load->view("catalog_search/compare",$data,TRUE);
		$this->template->write('title', t('title_compare_variables'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}

	/**
	*
	* List all variables selected for comparison
	*
	**/
	function variable_cart()
	{
		$this->lang->load('ddi_fields');
		$this->lang->load('catalog_search');
		$this->load->model("Dataset_model");
		$this->load->model("Variable_model");
		$this->load->model("Data_file_model");
		$this->load->helper("metadata_view");		

		$items=explode(",",$this->input->cookie('variable-compare', TRUE));
		$list=array();

		if (!$items){
			return false;
		}

		foreach($items as $item=>$value){
			$tmp=explode('/',$value);
			if (isset($tmp[1])){
				$variable=$this->Variable_model->variable_basic_info($tmp[0],$tmp[1]);
				if(!empty($variable)){
					$list[]=$variable;
				}
			}
		}
		
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($list));
		return;
	}


	function idno($codebookid=NULL){
		return $this->study($codebookid); 
	}

	function study($codebookid=NULL)
	{		
		if ($codebookid==NULL){
			show_404();
		}

		$sid=$this->Catalog_model->get_survey_uid($codebookid);

		if ($sid){
			redirect('catalog/'.$sid);
		}
		else{
			show_404();
		}
	}

	
	private function get_featured_study($surveys)
	{
		if (!is_array($surveys)){
			return FALSE;
		}

		$repos=NULL;

		//build an array of repositoryid
		foreach($surveys as $survey){
			if($survey['repositoryid']){
				$repos[]=$survey['repositoryid'];
			}
		}

		if (!$repos){
			return FALSE;
		}
		
		//count values for each repository
		$repo_counts = array_count_values($repos);

		//find the repo with most surveys
		$repositoryid = array_search(max($repo_counts), $repo_counts);

		//echo $repositoryid;

		//get the featured study for the selected repositoryid
		$featured_study=$this->Repository_model->get_featured_study($repositoryid);

		return $featured_study;
	}

	private function get_featured_studies_by_repo($repository_id=null,$study_type=null)
	{
		return $this->Repository_model->get_featured_study($repository_id,$study_type);
	}
}    
