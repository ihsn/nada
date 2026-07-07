<?php
/**
 * Catalog Maintenance Controller
 *
 * handles all Catalog Maintenance pages
 *
 */
class Collections extends MY_Controller {

  	public function __construct()
	{
      	parent::__construct();
		$this->load->model('Repository_model');
		$this->load->library('pagination');
		$this->load->helper('querystring_helper','url');
		$this->load->helper('form');
		//$this->load->helper("catalog");
		$this->template->set_template('admin');

		//load language file
		$this->lang->load('general');
		//$this->output->enable_profiler(TRUE);
	}



	/**
	 * Default page
	 *
	 */
	function index()
	{
		$this->acl_manager->require_collection_admin_list_access();

		$this->load->helper('vite_helper');
		$can_manage_collection_access = $this->acl_manager->user_has_any_collection_manage_access();

		$collections_view_data = [
			'api_base_url' => site_url('api/admin/collections/'),
			'site_url' => site_url(),
			'base_url' => base_url(),
			'csrf_token' => $this->security->get_csrf_hash(),
			'assets_base' => base_url('frontend/dist/'),
			'translations' => $this->lang->language,
			'can_manage_collection_access' => $can_manage_collection_access,
		];

		$page = [
			'title'           => t('collections'),
			'content'         => $this->load->view('admin/collections/index', $collections_view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		];
		$this->load->view('layouts/admin_vue', $page);
	}

	/**
	 * Set active repository for the user session (cookie) then redirect.
	 * Linked from admin collections UI, dashboard, and site menu.
	 */
	function active($repositoryid=NULL)
	{
		if (!is_numeric($repositoryid)){
			show_error("INVALID_ID");
		}

		$this->_require_active_repo_access((int) $repositoryid);

		$result = $this->Repository_model->set_active_repo($repositoryid);

		if ($result){
			if ($this->input->get('destination')){
				redirect($this->input->get('destination', true)); return;
			}
			redirect('admin/catalog');
		}

		show_error("CANT_SET_ACTIVE_REPO");
	}

	/**
	 * Clear active repository cookie.
	 */
	function reset_repo()
	{
		$this->acl_manager->require_catalog_access();
		$this->Repository_model->clear_active_repo();
	}

	/**
	 * Require study/view on the collection identified by repositories.id (0 = central).
	 *
	 * @param int $repo_pk
	 */
	private function _require_active_repo_access($repo_pk)
	{
		if ($repo_pk === 0) {
			$this->acl_manager->has_access_or_die('study', 'view', null, 'central');
			return;
		}

		$row = $this->Repository_model->select_single($repo_pk);
		if (empty($row) || empty($row['repositoryid'])) {
			show_error('REPOSITORY-NOT-FOUND');
		}

		$this->acl_manager->has_access_or_die('study', 'view', null, $row['repositoryid']);
	}

}
/* End of file collections.php */
/* Location: ./controllers/admin/collections.php */
