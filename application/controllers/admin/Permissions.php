<?php
class Permissions extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('form', 'url'));
       	//$this->load->model('User_Groups_model');
		$this->load->model('Permissions_model');
		//$this->load->model('Repository_model');
		
		//language files
		$this->lang->load('general');
		$this->lang->load('user_groups');
		$this->lang->load('permissions');
		//$this->output->enable_profiler(TRUE);

		//set default template
		$this->template->set_template('admin');		
	}

	
	function index()
	{
		echo "TODO";
	}
	
	function manage_group_perm($group_id=NULL)
	{
		if (!is_numeric($group_id))
		{
			show_404();
		}

		//get group info by group_id
		$group_info=$this->Permissions_model->get_group_info($group_id);
		
		if ($group_info===FALSE)
		{
			show_error('INVALID_GROUP');
		}
		
		//set group basic info
		$data['group_id']=$group_id;
		$data['group']=$group_info;
		
		//validation
		$this->form_validation->set_rules('pid[]', t('permission'), 'xss_clean|numeric');
		$this->form_validation->set_rules('repo[]', t('repository'), 'xss_clean|numeric');

		if ($this->form_validation->run() == TRUE)
		{
			//update group URL permissions
			$perms_array=$this->input->post("pid");
			$this->Permissions_model->update_perms($group_id,$perms_array);
			
			//update group REPO permissions
			$repos_array=$this->input->post("repo");
			$this->Permissions_model->update_repo_perms($group_id,$repos_array);
			
			$this->session->set_flashdata('message', t('form_update_success'));
		}
		
		//get all available permissions list
		$data['permissions']=$this->Permissions_model->get_grouped_permission_list();
		
		//array of permissions assigned to selected group
		$data['assigned_perms']=$this->Permissions_model->get_group_permissions($group_id);
		
		//get list of all repositories
		$data['repos']=$this->Permissions_model->get_repositories();
		
		//get group permissions for repositories
		$data['repo_group_perms']=$this->Permissions_model->get_group_repositories($group_id);
		
		$contents=$this->load->view('permissions/manage_group_perm',$data,TRUE);
		
		$this->template->write('content', $contents,true);
	  	$this->template->render();
	}
	
	
	
	/**
	*
	* Manage Permission URLs 
	**/
	function manage()
	{
		//get array of all permissions
		$data['permissions']=$this->Permissions_model->get_permission_labels();
		$data['permission_urls']=$this->Permissions_model->get_permission_urls();

		$contents=$this->load->view('permissions/index',$data,TRUE);
		
		$this->template->write('content', $contents,true);
	  	$this->template->render();
	}
	

	function edit($perm_id=NULL)
	{	
		if (!is_numeric($perm_id))
		{
			show_404();
		}
		
		//validation
		$this->form_validation->set_rules('label', t('permission'), 'xss_clean|trim|max_length[100]');
		$this->form_validation->set_rules('description', t('repository'), 'xss_clean|trim|max_length[100]');
		$this->form_validation->set_rules('section', t('permission'), 'xss_clean|trim|max_length[100]');
		$this->form_validation->set_rules('weight', t('weight'), 'xss_clean|numeric|max_length[3]');
		$this->form_validation->set_rules('url[]', t('URLs'), 'xss_clean|trim|max_length[100]');

		$perm_obj=array();	

		if ($this->form_validation->run() == TRUE)
		{		
			//update permission description
			$options=array(
						'label'			=>$this->input->post("label"),
						'section'		=>$this->input->post("section"),
						'description'	=>$this->input->post("description"),
						'weight'		=>$this->input->post("weight"),
						'url'			=>$this->input->post("url")
						);
			
			$is_saved=$this->Permissions_model->update_permission_options($perm_id,$options);
			
			if ($is_saved)
			{
				$this->session->set_flashdata('message', t('form_update_success'));
			}
			else
			{
				$this->session->set_flashdata('error', $this->db->_error_message());
			}
		}
		else
		{				
			//get permission info by id
			$perm_obj=$this->Permissions_model->get_permission_by_id($perm_id);			
		}
				
		$contents=$this->load->view('permissions/edit',$perm_obj,TRUE);
		$this->template->write('content', $contents,true);
	  	$this->template->render();		
	}	
	
	
	function add()
	{	
		//validation
		$this->form_validation->set_rules('label', t('permission'), 'xss_clean|trim|max_length[100]');
		$this->form_validation->set_rules('description', t('repository'), 'xss_clean|trim|max_length[100]');
		$this->form_validation->set_rules('section', t('permission'), 'xss_clean|trim|max_length[100]');
		$this->form_validation->set_rules('weight', t('weight'), 'xss_clean|numeric|max_length[3]');
		$this->form_validation->set_rules('url[]', t('URLs'), 'xss_clean|trim|max_length[100]');

		$perm_obj=array();	

		if ($this->form_validation->run() == TRUE)
		{		
			//update permission description
			$options=array(
						'label'			=>$this->input->post("label"),
						'section'		=>$this->input->post("section"),
						'description'	=>$this->input->post("description"),
						'weight'		=>$this->input->post("weight"),
						'url'			=>$this->input->post("url")
						);
			
			$is_saved=$this->Permissions_model->add_permission($options);
			
			if ($is_saved)
			{
				$this->session->set_flashdata('message', t('form_update_success'));
				redirect("admin/permissions/manage","refresh");
			}
			else
			{
				$this->session->set_flashdata('error', $this->db->_error_message());
			}
		}		
		
		$contents=$this->load->view('permissions/edit',$perm_obj,TRUE);
		$this->template->write('content', $contents,true);
	  	$this->template->render();		
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
				redirect('admin/menu',"refresh");
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
				redirect('admin/menu');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->Permissions_model->delete($item);
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
				redirect('admin/permissions/manage');
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