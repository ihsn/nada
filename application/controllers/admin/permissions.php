<?php
class Permissions extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('form', 'url'));
       	$this->load->model('User_Groups_model');
		$this->load->model('Permissions_model');
		$this->load->model('Repository_model');
		
		//menu language file
		$this->lang->load('general');
		$this->lang->load('user_groups');
		$this->lang->load('permissions');
		//$this->output->enable_profiler(TRUE);

		//set default template
		$this->template->set_template('admin');
		
	}
	
	/**
	  Ajax Functions
	  
	 */
	public function has_permission() {
		$group      = $this->input->get('group');
		$id         = (int) $this->input->get('id');
		echo (int) $this->Permissions_model->group_has_permission($group, $id);
	}
	public function add_permission() {
		$group = $this->input->get('group');
		$id    = (int) $this->input->get('id');
		if (!$this->Permissions_model->group_has_permission($group, $id)) {
			$this->Permissions_model->group_add_permission($group, $id);
		}
	}
	public function remove_permission() {
		$group = $this->input->get('group');
		$id    = (int) $this->input->get('id');
		$this->Permissions_model->group_delete_permission($group, $id);
		}
		
	public function add_repo() {
		$group = $this->input->get('group');
		$id    = (int) $this->input->get('id');
		$this->Repository_model->group_add_repo($group, $id);
	}
	public function remove_repo() {
		$group = $this->input->get('group');
		$id    = (int) $this->input->get('id');
		$this->Repository_model->group_remove_repo($group, $id);
	}
	/**
	  EOF
	 */
	 
	 public function test() {
		 if ($this->input->post('test')) {
			$group = (int) $this->input->post('group');
			$url   = $this->input->post('url');
			
			 if ($this->Permissions_model->group_has_url_access($group, $url)) {
	 			echo 'yes';
			 } else {
			    echo 'no';
			 }
		}
		$content=$this->load->view('permissions/test', null ,true);
	
		$this->template->write('content', $content,true);
		$this->template->write('title', t('permissions'),true);
	  	$this->template->render();	
	}
	
	public function admin() { 
		$data                = array();
		$data['permissions'] = $this->Permissions_model->get_ordered_permissions();

		$content=$this->load->view('permissions/admin', $data,true);
	
		$this->template->write('content', $content,true);
		$this->template->write('title', t('permissions_list'),true);
	  	$this->template->render();	
	}
	
	public function add($section_name=null) {
		$data                = array();

		$this->form_validation->set_rules('title', t('title'), 'xss_clean|required|max_length[255]');
		$this->form_validation->set_rules('description', t('description'), 'xss_clean|required|max_length[255]');
		$this->form_validation->set_rules('weight', t('weight'), 'required|numeric');
		$this->form_validation->set_rules('section', t('section'), 'xss_clean|required|max_length[45]');
		
		if ($this->form_validation->run() === TRUE) { 
			if ($this->input->post('submit')) {
				$permission = array(
					'label'       => $this->input->post('title'),
					'description' => $this->input->post('description'),
					'weight'      => $this->input->post('weight'),
					'section'     => $this->input->post('section'),
				);
				$this->Permissions_model->create_permission($permission);
				$id = $this->db->insert_id();
				if ($this->input->post('new')) {
					foreach($this->input->post('new') as $urls) {
						$data = array(
						'permission_id' => $id,
						'url'           => current($urls)
						);
						$this->Permissions_model->add_url($data);
					}
				}
				$this->session->set_flashdata('message', t('submitted'));
				redirect('admin/permissions/admin');
			}
		}
		$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

		$content=$this->load->view('permissions/add', $data,true);
				
		$this->template->write('content', $content,true);
		$this->template->write('title', t('add_permission'),true);
	  	$this->template->render();	
	}
	
	public function del($permission_id) {				
		if ($this->input->post('submit')!='') {
			$this->Permissions_model->delete_permission($permission_id);
			redirect('admin/permissions/admin');
		} else if ($this->input->post('cancel')!='') {
			redirect('admin/permissions/admin');
		}
		$content=$this->load->view('resources/delete', NULL,true);
			
		$this->template->write('content', $content,true);
  		$this->template->render();
	}
	
	public function delete($section) {				
		if ($this->input->post('submit')!='') {
			$permissions = $this->Permissions_model->get_permissions_by_section($section);
			foreach($permissions as $permission) {
				$this->Permissions_model->delete_permission($permission->id);
			}
			redirect('admin/permissions/admin');
		} else if ($this->input->post('cancel')!='') {
			redirect('admin/permissions/admin');
		}
		$content=$this->load->view('resources/delete', NULL,true);
			
		$this->template->write('content', $content,true);
  		$this->template->render();
	}
	
	public function edit($permission_id) { 
		$data                = array();
		$data['row']         = $this->Permissions_model->get_permission($permission_id);
		$data['urls']        = $this->Permissions_model->get_urls_by_permission_id($permission_id);

		$content=$this->load->view('permissions/edit', $data,true);
	
		if ($this->input->post('submit')) {
			$permission = array(
				'label'       => $this->input->post('title'),
				'description' => $this->input->post('description'),
				'weight'      => $this->input->post('weight'),
				'section'     => $this->input->post('section'),
			);
			$this->Permissions_model->update_permission($permission_id, $permission);
			$urls = $this->input->post('url');
			$x    = 0;
			foreach($this->input->post('url') as $id => $url) {
				$this->Permissions_model->update_urls($data['urls'][$x++]->url, array('url' => current($urls[$id])));
			}
			if ($this->input->post('new')) {
				foreach($this->input->post('new') as $urls) {
					$data = array(
					'permission_id' => $permission_id,
					'url'           => current($urls)
					);
					$this->Permissions_model->add_url($data);
				}
			}
			$this->session->set_flashdata('message', t('success'));
			redirect('admin/permissions/admin');
		}
		$this->template->write('content', $content,true);
		$this->template->write('title', t('edit_permission'),true);
	  	$this->template->render();	
	}
	
	public function index($usergroup_id=null) {
		if ($usergroup_id===null) {
			show_error('no group id');
		}
		$data                = array();
		$data['repos']       = $this->Repository_model->get_repositories();
		$data['permissions'] = $this->Permissions_model->get_ordered_permissions();
		$group               = $this->User_Groups_model->select_single($usergroup_id);
		$data['group']       = ucfirst($group['name']);
		$data['repo_access'] = $group['repo_access'];
		$data['repos_enabled']  = $this->Repository_model->group_repos($usergroup_id);
		$data['enabled']     = $this->Permissions_model->get_group_permissions($usergroup_id);
		
		$content=$this->load->view('permissions/index', $data,true);
	
		$this->template->write('content', $content,true);
		$this->template->write('title', t('permissions'),true);
	  	$this->template->render();	
	}
}