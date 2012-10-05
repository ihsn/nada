<?php
class Permissions extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('form', 'url'));
       	$this->load->model('User_Groups_model');
		$this->load->model('Permissions_model');
		
		//menu language file
		$this->lang->load('general');
		$this->lang->load('user_groups');
		$this->lang->load('permissions');
		
		//set default template
		$this->template->set_template('admin');
		
	}
	
	/**
	  Ajax Functions
	  
	 */
	public function has_permission() {
		$group = $this->input->get('group');
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
	
	public function index($usergroup_id) {
		if (!isset($usergroup_id)) {
			show_error('no group id');
		}
		$data                = array();
		$data['permissions'] = $this->Permissions_model->get_ordered_permissions();
		$group               = $this->User_Groups_model->select_single($usergroup_id);
		$data['group']       = ucfirst($group['name']);
		$data['enabled']     = $this->Permissions_model->get_group_permissions($usergroup_id);

		$content=$this->load->view('permissions/index', $data,true);
	
		$this->template->write('content', $content,true);
		$this->template->write('title', t('permissions'),true);
	  	$this->template->render();	
	}
}