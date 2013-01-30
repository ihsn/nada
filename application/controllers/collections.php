<?php
class Collections extends MY_Controller {

    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
		
       	$this->template->set_template('default');
		$this->template->write('sidebar', $this->_menu(),true);	
		$this->load->model('Search_helper_model');
		$this->load->model('Catalog_model');
		$this->load->library('pagination');
	 	//$this->output->enable_profiler(TRUE);
    		
		//language files
		$this->lang->load('general');
	}


	function index()
	{
		//reset any search options selected
		$this->session->unset_userdata('search');
		
		$this->load->model("repository_model");
		$this->load->model("repository_sections_model");
		
		//$sections=$this->repository_sections_model->select_all();
		$collections=$this->repository_model->get_repositories($published=FALSE, $system=FALSE);
		$sections=array();
		
		foreach($collections as $key=>$collection)
		{
			$sections[$collection['section']]=$collection['section_title'];
		}
		
		$data['sections']=$sections;		
		$data['rows']=$this->repository_model->get_repositories($published=FALSE, $system=FALSE);
		$data['show_unpublished']=TRUE;
		$content=$this->load->view("repositories/index_public",$data,TRUE);
				
		$this->template->write('title', t('home'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	function about($repositoryid)
	{
		if (strlen($repositoryid)>50)
		{
			show_404();
		}
		
		$this->load->model("repository_model");
		$repo=$this->repository_model->get_repository_by_repositoryid($repositoryid);
		
		if (!$repo)
		{
			show_404();
		}
		$data['repository']=(object)$repo;
		
		//data access summeries by repo
		$data['repo_data_access']=$this->repository_model->repo_survey_counts_by_data_access($repositoryid);//array('licensed','public')
		
		//surveys for the repo
		$data['surveys']= $this->repository_model->repo_survey_list($repositoryid);
		
		//data access by collection
		$data['allow_group_data_access']=$this->repository_model->repo_has_group_data_access($repositoryid);
				
		$contents=$this->load->view("repositories/about",$data,TRUE);
		$this->template->write('title', $repo['title'],true);
		$this->template->write('content', $contents,true);
	  	$this->template->render();
	}
	
	function _remap($method)
	{
		if (in_array(strtolower($method), array_map('strtolower', get_class_methods($this)))) 
		{
            $uri = $this->uri->segment_array();
            unset($uri[1]);
            unset($uri[2]);     
            call_user_func_array(array($this, $method), $uri);
        }
        else 
		{
			$this->load->model("repository_model");

			//get an array of all valid repository names from db
			$repositories=$this->Catalog_model->get_repository_array();
			$repositories[]='central';
			
			//check if URI matches to a repository name 
			if (in_array($method,$repositories))
			{
				//reset search options if visiting a different catalog
				$sess_repo=get_post_sess('search',"repo");
				
				if ((string)$sess_repo!=='' && $sess_repo!==$method)
				{
					//reset any search options selected
					$this->session->unset_userdata('search');
				}	
				
				//save active repository name in session
				$this->session->set_userdata('active_repository',$method);
				
				$this->about($this->uri->segment(2));return;

				if ($this->uri->segment(3)=='about')
				{
					$this->about($this->uri->segment(2));return;
				}
				
				//load the default listing page
				$this->index();
			}
			else
			{
				show_404();
			}		
        }

	}
	
}
/* End of file collections.php */
/* Location: ./controllers/collections.php */