<?php
class Collections extends MY_Controller {

    public function __construct()
	{
		parent::__construct($skip_auth=TRUE);

		$this->template->set_template('default');
		$this->load->model('Repository_model');
        $this->load->model("repository_sections_model");
		$this->load->library("Tokens");
        
		//$this->output->enable_profiler(TRUE);

		//language files
		$this->lang->load('general');
		$this->lang->load('catalog_search');		
    }
    
    function index($repositoryid='central')
	{
		$additional_data=NULL;
		$repo=NULL;

		if ($repositoryid=='central'){
			
			$collections=$this->repository_model->get_repositories($published=TRUE, $system=FALSE);
			$sections=array();

			foreach($collections as $key=>$collection){
				$sections[$collection['section']]=$collection['section_title'];
			}

			$data['sections']=$sections;
			$data['rows']=$collections;
			$data['show_unpublished']=FALSE;
			$additional_data=$this->load->view("repositories/index_public",$data,TRUE);
			$repo=array(
                'repositoryid'	=>'central',
                'title'			=>t('central_data_catalog')
			);
		}
		else
		{
			$repo=$this->repository_model->get_repository_by_repositoryid($repositoryid);

			if (!$repo){
				show_404();
			}

			$repo['long_text']=$this->tokens->replace_tokens($repo['long_text']);
			
		}

		$page_data=array(
			'repo'=>$repo['repositoryid'],
			'active_tab'=>'about',
			'repo_citations_count'=>null//$this->repository_model->get_citations_count_by_collection($this->active_repo['repositoryid'])
		);

		$content=$this->load->view("catalog_search/about_collection",array('row'=>(object)$repo, 'additional'=>$additional_data),TRUE);
		//$contents=$this->load->view("catalog_search/study_collection_tabs",$page_data,TRUE);

		//set page title
		$this->template->write('title', $repo['title'],true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
    }
    
}

