<?php
class Blocks extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
       	$this->load->model('block_model');
		$this->template->set_template('admin');
		$this->load->library('cache');
		
		$this->lang->load("general");
		//$this->lang->load("dashboard");
		$this->output->enable_profiler(TRUE);
    }
 
	function index()
	{	
		$data['title']='Dashboard';
		$content="content here";
		
		//set page options and render output
		$this->template->write('title', $data['title'],true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	//edit a block
	function edit($id=NULL)
	{
		if (!is_numeric($id))
		{
			show_error("INVALID ID");
		}

		$content="";

		$this->form_validation->set_rules('weight', t('weight'), 'xss_clean|numeric|max_length[3]');
		
		if ($this->form_validation->run() == FALSE)
		{
			$block=NULL;
			
			if ($this->input->post("submit"))
			{
				foreach($post_arr as $key=>$value)
				{
					$block[$key]=$this->input->post($key);
				}
			}
			else
			{
				//get block info from db
				$block=$this->block_model->get_single($id);			
			}
			
			//validation failed or viewing the page the first time
			$content=$this->load->view('blocks/edit',$block,true);
		}
		else
		{
			$options=array();
			$post_arr=$_POST;
						
			//read post values to pass to db
			foreach($post_arr as $key=>$value)
			{
				$options[$key]=$this->input->post($key);
			}
			
			//fix php open/close tags
			$options['body']=str_replace("&lt;?php","<?php",$options['body']);
			$options['body']=str_replace("?&gt;","?>",$options['body']);
			
			$options['bid']=$id;
																		
			$db_result=$this->block_model->update($id,$options);
			
			if ($db_result===TRUE)
			{
				//update successful
				$this->session->set_flashdata('message', t('form_update_success'));
				
				//redirect back to the list
				redirect("admin/blocks/edit/$id","refresh");
			}
			else
			{
				//update failed
				$this->form_validation->set_error(t('form_update_fail'));
			}			
		}

		//set page options and render output
		$this->template->write('title', 'Blocks',true);
		$this->template->write('content', $content,true);
	  	$this->template->render();

	}
	
	
}
/* End of file blocks.php */
/* Location: ./controllers/admin/blocks.php */