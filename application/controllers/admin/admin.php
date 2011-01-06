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
		$data['user_stats']=$this->Dashboard_model->get_user_stats();
		
		//process any bug report submits
		$this->_submit_bug_report();
		
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
		$this->simplepie->cache_location=$this->config->item("cache_path");

		//get news feed url from config
		$feed_url=$this->config->item("news_feed_url");
		
		if ($feed_url===FALSE)
		{
			//default feed url
			$feed_url="http://ihsn.org/nada/index.php?q=news/feed";
		}
		
		//get feed
		$this->simplepie->set_feed_url($feed_url);
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
		
		$cache_folder=$this->config->item("cache_path");
		
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
		$cache_folder=$this->config->item("cache_path");

		$k=0;
		foreach (glob($cache_folder."/*.cache") as $filename) 
		{
			$k++;
		}
		return $k;
	}
	
		
	function _submit_bug_report()
	{
		if (!$this->input->post("submit_bug"))
		{
			return;
		}

		$this->form_validation->set_rules('name', t('name'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('email', t('email'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('subject', t('subject'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('body', t('body'), 'xss_clean|trim|required|max_length[500]');

		//submit
		if ($this->form_validation->run() == TRUE)
		{
			//data for post
			$data=$_POST;
					
			//sanitize
			foreach($data as $key=>$value)
			{
				$data[$key]=$this->input->xss_clean($value);
			}
					
			//addition information
			$data['nada_app_version']=$this->config->item('app_version');
			$data['nada_db_version']=$this->config->item('db_version');
			$data['nada_url']=site_url();
			$data['php_version']=phpversion();
			
			//post form
			$response=$this->_http_post('http://www.ihsn.org/nada/report_bug.php',$data);

			if ($response==200)
			{
				$this->session->set_flashdata('message', t('form_update_success'));
				
				//redirect
				redirect("admin","refresh");
			}
			else
			{
				$this->form_validation->set_error(t('form_update_fail'));
			}
		}
	}


	/**
	*
	* Post form using http post to the IHSN server
	* bug tracker
	*
	* author: todo - find the original author of the function
	**/
	function _http_post($url, $data)
	{
	  $params = array('http' => array(
				  'method' => 'POST',
				  'content' => http_build_query($data)
	   ));
	
		//create context		
		$ctx = stream_context_create($params);
		
		//open handle
		$fp = @fopen($url, 'rb', false, $ctx);
		
		if (!$fp) 
		{
			return FALSE;
		}
		
		$response = @stream_get_contents($fp);
		
		if ($response === FALSE) 
		{
			return FALSE;
		}
		
		return $response;	  
	}
	
	
	
}
/* End of file admin.php */
/* Location: ./controllers/admin/admin.php */