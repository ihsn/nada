<?php
class Catalog extends MY_Controller {

	//active repository object
	var $active_repo=NULL;

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
        
		//$this->output->enable_profiler(TRUE);

		//language files
		$this->lang->load('general');
		$this->lang->load('catalog_search');

		//configuration settings
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
		$active_tab=xss_clean($this->input->get("tab_type"));
		$dataset_view='search/surveys';

		switch($active_tab){
			case 'survey':
				//$dataset_view=
			case '':
				break;

			case 'image':
				$dataset_view='search/images';
				break;
		}

		$output= $this->_search();		
		$output['featured_studies']=null; //$this->get_featured_study($output['surveys']['rows']);
		$output['search_output']=$this->load->view($dataset_view, $output,true);

		$filters=array();

		//TODO:: active repo?
		//set/get active repositoryid

		//get years
		$filters['years']=$this->Search_helper_model->get_years_range();		
		$filters['years']=$this->load->view('search/filter_years',$filters,true);

		//collections
		$filters['repositories']=$this->Repository_model->get_repositories_with_survey_counts();
		$filters['repositories']=$this->load->view('search/filter_collections', $filters,true);

		//data access types
		$filters['da_types']=$this->Search_helper_model->get_active_data_types();
		$filters['da_types']=$this->load->view('search/filter_da', $filters,true);

		//countries
		$filters['countries']=$this->Search_helper_model->get_active_countries();
		$filters['countries']=$this->load->view('search/filter_countries', $filters,true);

		//types
		$types=$this->Search_helper_model->get_dataset_types();

		//types filter
		if(!$active_tab){
			$filters['types']=$this->load->view('search/filter_types', array('types'=>$types),true);
		}

		$output['filters']=$filters;


		//tabs
		$tabs=array();

		$tabs['types']=$types;
		$tabs['search_counts_by_type']=$output['surveys']['search_counts_by_type'];
		$tabs['active_tab']=xss_clean($this->input->get("tab_type"));

		$output['tabs']=$tabs;

		//search_counts_by_type

        $content=$this->load->view('search/layout',$output,true);
		$this->template->write('title', 'title',true);
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
		$active_tab=xss_clean($this->input->get("tab_type"));
		$dataset_view='search/surveys';

		switch($active_tab){
			case 'survey':
				//$dataset_view=
			case '':
				break;

			case 'image':
				$dataset_view='search/images';
				break;
		}

		$output= $this->_search();
		$output['featured_studies']=null;//$this->get_featured_study($output['surveys']['rows']);
		$this->load->view($dataset_view, $output);		
	}


	function _search()
	{
		//all keys that needs to be persisted
		$get_keys_array=array('tab_type','sort_order','sort_by','sk','vk','vf','from','to','country','view','topic','page','repo','sid','collection');

		$this->load->helper('security');

		//get year min/max
		$data['min_year']=$this->Search_helper_model->get_min_year();
		$data['max_year']=$this->Search_helper_model->get_max_year();

		$search_options=new StdClass;
		$search_options->filter= new StdClass;
		$limit=15;

		//page parameters
		$search_options->collection		=xss_clean($this->input->get("collection"));
		$search_options->sk				=trim(xss_clean($this->input->get("sk")));
		$search_options->vk				=trim(xss_clean($this->input->get("vk")));
		$search_options->vf				=xss_clean($this->input->get("vf"));
		$search_options->country		=xss_clean($this->input->get("country"));
		$search_options->view			=xss_clean($this->input->get("view"));
		$search_options->topic			=xss_clean($this->input->get("topic"));
		$search_options->from			=xss_clean($this->input->get("from"));
		$search_options->to				=xss_clean($this->input->get("to"));
		$search_options->sort_by		=xss_clean($this->input->get("sort_by"));
		$search_options->sort_order		=xss_clean($this->input->get("sort_order"));
		$search_options->page			=(int)xss_clean($this->input->get("page"));
		$search_options->page			=($search_options->page >0) ? $search_options->page : 1;
		$search_options->filter->repo	=xss_clean($this->active_repo['repositoryid']);
		$search_options->dtype			=xss_clean($this->input->get("dtype"));
		$search_options->sid			=xss_clean($this->input->get("sid"));
		$search_options->type			=xss_clean($this->input->get("type"));
		$search_options->country_iso3	=xss_clean($this->input->get("country_iso3"));
		$search_options->tab_type		=xss_clean($this->input->get("tab_type"));
		$offset=						($search_options->page-1)*$limit;

		//allowed fields for sort_by and sort_order
		$allowed_fields = array('proddate','title','labl','nation','popularity','rank');
		$allowed_order=array('asc','desc');

		//set default sort options, if passed values are not valid
		if (!in_array(trim($search_options->sort_by),$allowed_fields))
		{
			$search_options->sort_by='';
		}

		//default for sort order if no valid values found
		if (!in_array($search_options->sort_order,$allowed_order))
		{
			$search_options->sort_order='';
		}

		//log
		$this->db_logger->write_log('search',$this->input->get("sk").'/'.$this->input->get("vk"),'sk-vk');

		//get list of all repositories
		$data['repositories']=$this->Catalog_model->get_repositories();

		//if ($this->regional_search)
		//{
			$data['countries']=$this->Search_helper_model->get_active_countries($this->active_repo['repositoryid']);
		//}

		/*if($this->topic_search=='yes')
		{
			//get vocabulary id from config
			$vid=$this->config->item("topics_vocab");

			if ($vid!==FALSE && is_numeric($vid))
			{
				//$this->load->model('Vocabulary_model');
				$this->load->model('term_model');

				//get topics by vid
				$data['topics']=$this->Vocabulary_model->get_terms_array($vid,$active_only=TRUE);//$this->Vocabulary_model->get_tree($vid);
				$data['topic_search']=TRUE;
			}
			else
			{
				//hide the topics box
				$data['topic_search']='no';
			}
		}*/


		//which view to use for display
		if ($search_options->vk!='' && $search_options->view=='v')
		{
			//variable search
			$params=array(
				'collections'=>$search_options->collection,
				'study_keywords'=>$search_options->sk,
				'variable_keywords'=>$search_options->vk,
				'variable_fields'=>$search_options->vf,
				'countries'=>$search_options->country,
				'topics'=>$search_options->topic,
				'from'=>$search_options->from,
				'to'=>$search_options->to,
				'sort_by'=>$search_options->sort_by,
				'sort_order'=>$search_options->sort_order,
				'repo'=>$search_options->filter->repo,
				'dtype'=>$search_options->dtype
			);

			$this->load->library('catalog_search',$params);
			$search_result=$this->catalog_search->vsearch($limit,$offset);

			$data=array_merge($search_result,$data);
			$data['current_page']=$search_options->page;
			$data['search_options']=$search_options;
			$data['data_access_types']=$this->Form_model->get_form_list();
			$data['search_type']='variable';
			return $data;

		}

		$params=array(
			'collections'=>$search_options->collection,
			'study_keywords'=>$search_options->sk,
			'variable_keywords'=>$search_options->vk,
			'variable_fields'=>$search_options->vf,
			'countries'=>$search_options->country,
			'topics'=>$search_options->topic,
			'from'=>$search_options->from,
			'to'=>$search_options->to,
			'sort_by'=>$search_options->sort_by,
			'sort_order'=>$search_options->sort_order,
			'repo'=>$search_options->filter->repo,
			'dtype'=>$search_options->dtype,
			'sid'=>$search_options->sid,
			'type'=>$search_options->type,
            'country_iso3'=>$search_options->country_iso3,
		);


		$this->load->library('catalog_search',$params);
		$data['surveys']=$this->catalog_search->search($limit,$offset);
		$data['current_page']=$search_options->page;
		$data['search_options']=$search_options;
		$data['data_access_types']=$this->Form_model->get_form_list();
		$data['sid']=$search_options->sid;
		$data['search_type']='study';
		return $data;
	}

}    
