<?php
class Admin extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
       	$this->load->model('Dashboard_model');
    		$this->load->model('Repository_model');
    		$this->template->set_template('admin');
    		$this->load->driver('cache', array('adapter' => 'dummy', 'backup' => 'file'));

    		$this->lang->load("general");
    		$this->lang->load("dashboard");
    		//$this->output->enable_profiler(TRUE);
    }

	function index()
	{
		$this->load->helper('date_helper');

		$data['title']=t('Dashboard');
		$data['recent_studies']=$this->_get_recent_studies();
		$data['cache_files']=$this->_cache_file_count();
		$data['user_stats']=$this->Dashboard_model->get_user_stats();
		$data['collections']=$this->_repository_stats();
		$data['failed_email_count']=$this->Dashboard_model->get_failed_email_count();
		$data['sitelog_count']=$this->Dashboard_model->get_sitelog_count();
		$content=$this->load->view('dashboard/index',$data,TRUE);
		$this->template->write('title', $data['title'],TRUE);
		$this->template->write('content', $content,TRUE);
	  	$this->template->render();
	}


	/**
	*
	* Clear cached files/folder
	*
	*
	**/
	function clear_cache()
	{
		$this->cache->clean();
		$this->session->set_flashdata('message', 'Cache is cleared!');
		redirect("admin","refresh");
	}

	/**
	*
	* Return count for the Cached files
	*
	**/
	function _cache_file_count()
	{
		return $this->cache->cache_info();
	}


	/**
	*
	*Return top N recent studies
	**/
	function _get_recent_studies()
	{
		$this->db->select("id,title,changed,repositoryid,created");
		$this->db->limit(15);
		$this->db->order_by('changed', 'DESC');
		return $this->db->get("surveys")->result_array();
	}

	/**
	*
	* Return summary stats for all user owned repositories
	**/
	function _repository_stats()
	{
		$user_repos=$this->acl->get_user_repositories();

		//array_unshift($user_repos, $this->Repository_model->get_central_catalog_array()	);

		foreach($user_repos as $key=>$repo)
		{
			$user_repos[$key]['stats']=$this->Repository_model->get_summary_stats($repo['repositoryid']);
		}

		return $user_repos;
	}

}
/* End of file admin.php */
/* Location: ./controllers/admin/admin.php */
