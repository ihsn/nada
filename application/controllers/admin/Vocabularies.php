<?php
class Vocabularies extends MY_Controller {
 
    function __construct() 
    {
        parent::__construct();   
		$this->load->model('vocabulary_model');
		$this->template->set_template('admin');
    	
		$this->lang->load('general');
		$this->lang->load('vocabularies');	
	}
 
 
	function index()
	{
		$data['rows']=$this->vocabulary_model->select_all();
		$content=$this->load->view('vocabularies/index', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('vocabularies'),true);
	  	$this->template->render();
	}
	
	function edit($id=NULL)
	{
	
		if (!is_numeric($id) && $id!=NULL)
		{
			show_error("INVALID ID");
		}
	
		$data=NULL;
		
		//validation rules
		$this->form_validation->set_rules('title', t('title'), 'trim|required|max_length[255]');
				
		//process form				
		if ($this->form_validation->run() == TRUE)
		{
			$title=$this->input->post("title");
			$vid=$this->uri->segment(4);
			
			if ($id==NULL)
			{
				//insert
				$db_result=$this->vocabulary_model->insert($title);
			}
			else
			{
				//update
				$db_result=$this->vocabulary_model->update($vid,$title);
			}

			if ($db_result!==FALSE)
			{
				//update successful
				$this->session->set_flashdata('message', t('form_update_success'));
				
				//redirect back to the list
				redirect("admin/vocabularies","refresh");
			}
			else
			{
				//update failed
				$this->form_validation->set_error(t('form_update_fail'));
			}
		}
		else //loading form the first time
		{
				if ( is_numeric($id) )
				{
					//get menu from db
					$row=$this->vocabulary_model->select_single($id);

					if ( $row===FALSE || count($row)==0)
					{
						show_error("INVALID ID");
					}
				
					$data['title']=$row['title'];				
				}
		}

		$this->html_form_url=site_url().'/admin/vocabularies';
		
		//show the form
		if ($id==NULL)
		{
			$data['page_title']=t('add_vocabulary');
			$this->html_form_url.='/add';
		}
		else
		{
			$data['page_title']=t('edit_vocabulary');
			$this->html_form_url.='/edit/'.$id;
		}		

		$content=$this->load->view('vocabularies/edit', $data,TRUE);
		
		//render the template
		$this->template->write('content', $content,true);
		$this->template->write('title', t('title_vocabulary'),true);
	  	$this->template->render();
	}
	
	
	function add()
	{		
		$this->edit(NULL);
	}

	/**
	* Delete one or more records
	* note: to use with ajax/json, pass the ajax as querystring
	* 
	* id 	int or comma seperate string
	*/
	function delete($id)
	{			
		//array of id to be deleted
		$delete_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($id))
		{
			$tmp_arr=explode(",",$id);
		
			foreach($tmp_arr as $key=>$value)
			{
				if (is_numeric($value))
				{
					$delete_arr[]=$value;
				}
			}
			
			if (count($delete_arr)==0)
			{
				//for ajax return JSON output
				if ($ajax!='')
				{
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
				}
				
				$this->session->set_flashdata('error', 'Invalid id was provided.');
				redirect('admin/vocabularies',"refresh");
			}	
		}		
		else
		{
			$delete_arr[]=$id;
		}
		
		if ($this->input->post('cancel')!='')
		{
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/vocabularies');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->vocabulary_model->delete($item);
			}

			//for ajax calls, return output as JSON						
			if ($ajax!='')
			{
				echo json_encode(array('success'=>"true") );
				exit;
			}
						
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/vocabularies');
			}	
		}
		else
		{
			//ask for confirmation
			$content=$this->load->view('resources/delete', NULL,true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}
}    