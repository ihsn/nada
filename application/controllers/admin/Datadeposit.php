<?php
class Datadeposit extends MY_Controller {

	private $storage_location=NULL;

	public function __construct() 
	{
		parent::__construct();
		$this->load->helper('datadeposit');
		$this->load->language('dd_projects');
		$this->lang->load('general');
		$this->template->set_template('admin5');

		if (!datadeposit_is_enabled()) {
			return;
		}

		$this->load->model('DD_project_model');
		$this->lang->load("dashboard");
		$this->load->library('form_validation');
		$this->lang->load('licensed_request');
		$this->lang->load('catalog_admin');
		
		$this->load->config("datadeposit");
		
		//location where data is stored
		$this->storage_location= $this->config->item('datadeposit');
		$this->storage_location = $this->storage_location['resources'];

		$this->acl_manager->has_access_or_die('datadeposit', 'view');
	}
	
	public function index()
	{
		$this->_render_admin_datadeposit_vue_shell();
	}

	public function tasks()
	{
		$this->_render_admin_datadeposit_vue_shell(t('Active tasks'));
	}

	public function my_tasks()
	{
		$this->_render_admin_datadeposit_vue_shell(t('My Tasks'));
	}

	public function task_info($task_id = null)
	{
		if (!is_numeric($task_id)) {
			show_404();
		}

		$this->load->model('DD_tasks_model');
		$task = $this->DD_tasks_model->select_single($task_id);
		if (!$task) {
			show_404();
		}

		$this->_render_admin_datadeposit_vue_shell(t('Task info'));
	}

	/**
	 * Shared config for the staff data-deposit Vue app.
	 *
	 * @return array
	 */
	private function _admin_datadeposit_vue_view_data()
	{
		$this->load->helper('vite_helper');
		$pu = parse_url(site_url('admin/datadeposit'));
		$rpath = isset($pu['path']) ? $pu['path'] : '/admin/datadeposit';

		return array(
			'api_base_url' => site_url('api/admin/datadeposit/'),
			'site_url' => site_url(),
			'base_url' => base_url(),
			'csrf_token' => $this->security->get_csrf_hash(),
			'csrf_token_name' => $this->security->get_csrf_token_name(),
			'assets_base' => base_url('frontend/dist/'),
			'translations' => $this->lang->language,
			'router_path_base' => $rpath,
			'can_edit' => $this->acl_manager->user_has_access('datadeposit', 'edit'),
			'can_delete' => $this->acl_manager->user_has_access('datadeposit', 'delete'),
		);
	}

	/**
	 * Render Vue 3 shell for staff data deposit.
	 *
	 * @param string|null $html_title
	 * @return void
	 */
	private function _render_disabled()
	{
		$page = array(
			'title' => t('title_project_management'),
			'content' => $this->load->view('admin/datadeposit/disabled', null, true),
			'hide_breadcrumb' => true,
			'theme_folder' => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}

	private function _render_admin_datadeposit_vue_shell($html_title = null, $blank = false)
	{
		$view_data = $this->_admin_datadeposit_vue_view_data();
		$page = array(
			'title' => $html_title !== null ? $html_title : t('title_project_management'),
			'content' => $this->load->view('admin/datadeposit/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder' => 'adminvue',
		);
		$layout = $blank ? 'layouts/admin_vue_blank' : 'layouts/admin_vue';
		$this->load->view($layout, $page);
	}
	
	function projects($id=null, $tab=null)
	{
		if (!is_numeric($id))
		{
			show_404();
		}

		$project = $this->DD_project_model->get_by_id($id);
		if (!$project)
		{
			show_404();
		}

		$title = !empty($project['title']) ? $project['title'] : t('title_project_management');
		$this->_render_admin_datadeposit_vue_shell($title);
	}

	public function _remap($method, $params = array())
	{
		if (!datadeposit_is_enabled()) {
			$this->_render_disabled();
			return;
		}

		$keep = array(
			'index',
			'tasks',
			'my_tasks',
			'task_info',
			'projects',
			'summary',
			'assign',
			'old_folder_paths',
			'update_folder_paths',
		);
		if (in_array($method, $keep, true) && method_exists($this, $method)) {
			return call_user_func_array(array($this, $method), $params);
		}
		show_404();
	}
	
	
	//prints folders using older method
	function old_folder_paths()
	{
		$this->acl_manager->has_access_or_die('datadeposit', 'edit');

		$this->db->select("id,created_on,title");
		$projects=$this->db->get('DD_projects')->result_array('DD_projects');
		
		$storage_location=$this->storage_location;
	
		echo '<pre>';
		foreach($projects as $project)
		{
			$md5=md5($project['id'] . $project['created_on']);
			$folder=$storage_location.'/'.$md5;
			echo $project['id']."\t".$project['created_on'];
			echo "\t" .$md5;
			echo "\t". $folder;
			if (file_exists($folder)){
				echo "\t YES";
			}
			echo "\n";
		}
		echo '</pre>';		
	}
	
	function update_folder_paths()
	{
		$this->acl_manager->has_access_or_die('datadeposit', 'edit');

		$this->db->select("id,created_on,title,data_folder_path");
		$projects=$this->db->get('DD_projects')->result_array('dd_projects');
		
		$storage_location=$this->storage_location;
		
		foreach($projects as $project)
		{
			if (!$project['data_folder_path'])
			{
				$md5=md5($project['id'] . $project['created_on']);
				$folder=$storage_location.'/'.$md5;
			
				if (file_exists($folder))
				{
					$this->DD_project_model->set_project_folder($project['id'],$md5);
					echo "updated project " .$project['id']."<BR>";
				}
			}
		}
	}

    public function summary($id) {

		if (!is_numeric($id))
		{
			show_404();
		}

		$project = $this->DD_project_model->get_by_id($id);
		if (!$project || empty($project['id']))
		{
			show_404();
		}

		$title = !empty($project['title']) ? $project['title'] : t('summary');
		$this->_render_admin_datadeposit_vue_shell($title, true);
    }



    function assign($project_id)
    {
        $this->acl_manager->has_access_or_die('datadeposit', 'edit');

        if (!is_numeric($project_id))
        {
            show_404();
        }

        $project = $this->DD_project_model->get_by_id($project_id);
        if (!$project)
        {
            show_404();
        }

        $title = !empty($project['title']) ? $project['title'] : t('Assign task');
        $this->_render_admin_datadeposit_vue_shell($title);
    }



}	