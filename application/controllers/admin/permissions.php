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
		$group_name = $this->input->get('group_name');
		$id         = (int) $this->input->get('id');
		echo (int) $this->Permissions_model->group_has_permission($group_name, $id);
	}
	public function add_permission() {
		$group_name = $this->input->get('group_name');
		$id         = (int) $this->input->get('id');
		if (!$this->Permissions_model->group_has_permission($group_name, $id)) {
			$this->Permissions_model->group_add_permission($id);
		}
	}
	public function remove_permission() {
		$group_name = $this->input->get('group_name');
		$id         = (int) $this->input->get('id');
		$this->Permissions_model->group_delete_permission($id);
		}
	/**
	  EOF
	 */
	 
	public function index() {
		$data                = array(); // doinitrite.
		$data['group_names'] = $this->Permissions_model->get_ordered_groups();
		$data['permissions'] = $this->Permissions_model->get_ordered_permissions();
		$data['enabled']     = $this->Permissions_model->get_enabled_permissions();
		
		$content=$this->load->view('permissions/index', $data,true);
	
		$this->template->write('content', $content,true);
		$this->template->write('title', t('permissions'),true);
	  	$this->template->render();	
	}
}