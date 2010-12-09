<?php
class Terms extends MY_Controller {
 
    function __construct() 
    {
        parent::__construct();
		$this->load->model('term_model');
		$this->template->set_template('admin');
		
		$this->lang->load('general');
		$this->lang->load('vocabularies');	
    }
 
	function index($vid=NULL)
	{
		if (!is_numeric($vid))
		{
			show_404();
		}
		
		$vocab_title=$this->term_model->get_vocabulary_title($vid);
		
		if($vocab_title===FALSE)
		{
			show_404();
		}
		
		$data['page_title']=$vocab_title;		
		$data['rows']=$this->term_model->get_terms_tree($vid,$tid=0);//$this->term_model->select_all($vid);
		$content=$this->load->view('terms/index', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', $data['page_title'],true);
	  	$this->template->render();
	}
	
	function edit($id=NULL)
	{
		if (!is_numeric($id) && $id!=NULL)
		{
			show_404();
		}
	
		$data=NULL;
		
		//vocabulary id
		$this->vid=$this->uri->segment(3);		
		
		//validation rules
		$this->form_validation->set_rules('title', t('title'), 'trim|required|max_length[255]');
				
		//process form				
		if ($this->form_validation->run() == TRUE)
		{
			$title=$this->input->post("title");
			$tid=$this->uri->segment(5);
			$pid=$this->input->post("pid");
			
			$dbdata['title']=$title;
			$dbdata['pid']=$pid;
			$dbdata['vid']=$this->vid;
			
			if ($id==NULL)
			{
				//insert
				$db_result=$this->term_model->insert($dbdata);
			}
			else
			{
				//update
				$db_result=$this->term_model->update($tid,$dbdata);
			}
						
			if ($db_result===TRUE)
			{
				//update successful
				$this->session->set_flashdata('message', t('form_update_success'));
				
				//redirect back to the list
				redirect("admin/terms/".$this->vid,"refresh");
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
					$row=$this->term_model->select_single($id);
								
					if (!$row)
					{
						show_404();
					}
				
					$data['title']=$row['title'];
					$data['pid']=$row['pid'];
					$data['vid']=$row['vid'];
				}
		}

		//show the form
		if ($id==NULL)
		{
			$data['page_title']=t('add_term');
			$data['pid']=0;
		}
		else
		{
			$data['page_title']=t('edit_term');
		}		

		//load the tree listing of terms
		$this->term_tree=$this->term_model->get_terms_tree($this->vid,$tid=0,$show_root=TRUE);
				
		//show the form
		$content=$this->load->view('terms/edit', $data,TRUE);
		
		//render the template
		$this->template->write('content', $content,true);
		$this->template->write('title', t('title_terms'),true);
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
				redirect('admin/terms/'.$this->uri->segment(3));
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->term_model->delete($item);
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
				redirect('admin/terms/'.$this->uri->segment(3));
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

	function test()
	{
			$vid=1;
			if ($this->input->get("vid"))
			{
				$vid=$this->input->get("vid");
			}

		$terms_array=$this->term_model->get_terms_tree_array($vid,$tid=0);
		$this->load->view('test/topics',array('topics'=>$terms_array));
		//echo '<pre>';
		//print_r($terms_array);
	
		return;
			//echo '<pre>';
			echo '
				<style>
					.topic-heading{font-weight:bold;float:left; display: inline-block;padding-right:10px;width:40%}
					.topic{font-weight:normal;}
				</style>
			';
			$show_only=NULL;//array(70,74);
			$vid=1;
			
			if ($this->input->get("vid"))
			{
				$vid=$this->input->get("vid");
			}
			echo $this->term_model->get_formatted_terms_tree($vid,0,$show_only) ;return;
			//var_dump($this->term_model->get_terms_tree(1,$tid=0));
	}
	
	
	function _remap()
	{
		$controller=$this->uri->segment(3);
		$method=$this->uri->segment(4);
		
		switch($method)
		{
			case 'edit':
				$this->edit($this->uri->segment(5));
			break;
			
			case 'delete':
				$this->delete($this->uri->segment(5));
			break;
			
			case 'add':
				$this->add();
			break;
		
			case 'test':
				$this->test();
			break;
			//index page
			default:
			$this->index($controller);
		}
	}
}    