<?php
class Admin extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
       	$this->load->model('Dashboard_model');
		$this->load->model('Repository_model');
		$this->template->set_template('admin5'); 
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

		$repos=$this->Repository_model->select_all();
		array_unshift($repos, $this->Repository_model->get_central_catalog_array()	);

		foreach($repos as $key=>$repo){
			$repos[$key]['stats']=$this->Repository_model->get_summary_stats($repo['repositoryid']);
		}

		return $repos;
	}

}
/* End of file admin.php */
/* Location: ./controllers/admin/admin.php */
