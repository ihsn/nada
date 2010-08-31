<?php
class Admin extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
       	$this->load->model('Dashboard_model');
		$this->template->set_template('admin');
		$this->load->library('cache');
		
		$this->lang->load("general");
		$this->lang->load("dashboard");
    }
 
	function index()
	{	
		$data['title']='Dashboard';
		
		//News
		$data['news']=$this->news(true);
		
		//cached files count
		$data['cache_files']=$this->_cache_file_count();
	
		//user status
		$data['user_stats']=$this->Dashboard_model->get_user_stats();;
		
		//load the contents of the page into a variable
		$content=$this->load->view('dashboard',$data,TRUE);
		
		//set page title
		$this->template->write('title', $data['title'],true);
		
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	function news($output=FALSE)
	{
		$this->load->library('simplepie');
		
		//set cache path from the CI config
		$this->simplepie->cache_location=$this->config->item("cache_folder");
		
		//get feed
		$this->simplepie->set_feed_url("http://ihsn.org/mantis/issues_rss.php");
		$this->simplepie->set_timeout(5);//time to wait for the feed
		$this->simplepie->init();
		$data['feed'] = $this->simplepie;
		
		//display items
		return $this->load->view('simplepie/index',$data,$output);
	}
	
	/**
	*
	* Clear cached files/folder
	*
	*
	**/
	function clear_cache()
	{
		//delete cache files
		//$this->cache->delete_all();
		
		$cache_folder=$this->config->item("cache_folder");
		
		if ($cache_folder==false)
		{
			return FALSE;
		}
		
		//iterate and remove files
		foreach (glob($cache_folder."/*.cache") as $filename) 
		{
			@unlink($filename);
		}

		//update successful
		$this->session->set_flashdata('message', 'Cache is cleared!');
			
		//redirect back to the list
		redirect("admin","refresh");
	}
	
	/**
	*
	* Return count for the Cached files
	*
	**/
	function _cache_file_count()
	{
		$cache_folder=$this->config->item("cache_folder");

		$k=0;
		foreach (glob($cache_folder."/*.cache") as $filename) 
		{
			$k++;
		}
		return $k;
	}
	
	
	
}
/* End of file admin.php */
/* Location: ./controllers/admin/admin.php */