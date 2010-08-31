<?php
class Filemanager extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
		$this->template->set_template('blank');
    }
 
	function index()
	{	
		$this->load->helper('directory');
		$this->load->helper('file');
		
		$subfolder=$this->input->get('folder');
		$folder_path='../nada2.1_data/'.$subfolder.'/';
		
		
		$data['files']= directory_map($folder_path,true) ;

		$files=array();
		foreach($data['files'] as $key=>$filename)
		{
			//get file information
			$info=get_file_info($folder_path.$filename, array('fileperms', 'size'));
			//echo symbolic_permissions($info['fileperms']);	
			
			$tmp['name']=$filename;		
			$tmp['perms']=symbolic_permissions($info['fileperms']);
			$tmp['size']=ceil($info['size']/1024).' kb';
			$tmp['folder']=is_dir($folder_path.$filename) ;
			
			$files[]=$tmp;
		}

//		var_dump($data['files']);exit;
		
		$data['files']=$files;
		
		$content=$this->load->view('filemanager/index',$data,TRUE );

		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();

		//require_once FCPATH.'modules/quixplorer/index.php';
	/*
		if ($this->uri->segment(1)=='admin')
		{
			$data['title']='admin page - set to home page'. uri_string();
			$data['body']='Site page content here....';		
		}
		
		//load the contents of the page into a variable
		$content=$this->load->view('page_index', $data,true);
		
		//set page title
		$this->template->write('title', $data['title'],true);
		
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
		*/	
	}
	
}
/* End of file filemanager.php */
/* Location: ./controllers/admin/filemanager.php */